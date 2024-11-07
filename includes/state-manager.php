<?php
// app/public/wp-content/plugins/cyoa-interactive-story-builder/includes/state-manager.php

class IASB_State_Manager {
    private $user_id;
    private $story_id;
    private $state;
    private $character_data;
    

    public function __construct($user_id, $story_id) {
        $this->user_id = $user_id;
        $this->story_id = $story_id;
        $this->state = $this->get_story_state();
        $this->character_data = $this->get_character_data();
        // Add debug output
        //error_log("State loaded in constructor: " . print_r($this->state, true));
    }

    private function get_character_data() {
        $character_data = get_user_meta($this->user_id, 'adventure_game_character', true) ?: [];
        
        // Ensure character attributes are lowercase for consistency
        if (isset($character_data['Attributes'])) {
            $character_data['Attributes'] = array_change_key_case($character_data['Attributes'], CASE_LOWER);
        }
        
        return $character_data;
    }

    public function get_story_state() {
        if (is_user_logged_in()) {
            $state = get_user_meta($this->user_id, 'iasb_story_state_' . $this->story_id, true);
        } else {
            $state = isset($_SESSION['iasb_story_state_' . $this->story_id]) ? $_SESSION['iasb_story_state_' . $this->story_id] : array();
        }
        return !empty($state) ? $state : $this->initialize_state();
    }

    private function initialize_state() {
        return array(
            'inventory' => array(),
            'flags' => array(),
            'relationships' => array(),
            'stats' => array(),
            'visited_nodes' => array(),
            'choices_made' => array(),
            'achievements' => array(),
            'variables' => array(),
            'strength' => 15,
        );
    }

    public function save_state() {
        if (is_user_logged_in()) {
            update_user_meta($this->user_id, 'iasb_story_state_' . $this->story_id, $this->state);
        } else {
            $_SESSION['iasb_story_state_' . $this->story_id] = $this->state;
        }
        // Add debug output
        //error_log("State saved: " . print_r($this->state, true));
    }

    // Condition evaluation methods
    public function evaluate_condition($condition) {
        //error_log('State Manager - Evaluating condition: ' . $condition);
    
        $parts = preg_split('/\s+/', trim($condition), 3);
        //error_log('State Manager - Condition parts: ' . print_r($parts, true));
    
        if (count($parts) === 3) {
            $variable = $parts[0];
            $operator = $parts[1];
            $value = $parts[2];
    
            $current_value = $this->get_variable($variable);
    
            //error_log("State Manager - Variable: $variable, Current Value: $current_value, Operator: $operator, Comparison Value: $value");
    
            $result = false;
            switch ($operator) {
                case '==':
                    $result = $current_value == $value;
                    break;
                case '>':
                    $result = floatval($current_value) > floatval($value);
                    break;
                case '<':
                    $result = floatval($current_value) < floatval($value);
                    break;
                case '>=':
                    $result = floatval($current_value) >= floatval($value);
                    break;
                case '<=':
                    $result = floatval($current_value) <= floatval($value);
                    break;
            }
            //error_log("State Manager - Condition evaluation result: " . ($result ? 'true' : 'false'));
            return $result;
        }
        //error_log("State Manager - Invalid condition format");
        return false;
    }

    // Complex condition evaluation method
    public function evaluate_complex_condition($condition) {
        $context = array_merge(
            $this->get_all_state_variables(),
            $this->get_all_character_attributes(),
            $this->get_all_quest_progress()
        );

        error_log("Evaluation context in public function evaluate_complex_condition--> " . print_r($context, true));

        // Use safe_evaluate instead of eval
        $result = $this->safe_evaluate($condition);

        if ($result === false) {
            error_log('Failed to evaluate condition in public function evaluate_complex_condition--> ' . $condition);
            return false;
        }

        error_log("Condition result in public function evaluate_complex_condition--> " . ($result ? 'true' : 'false'));
        return $result;
    }


    // Get state variable
    public function get_state_variable($name) {
        return $this->state['variables'][$name] ?? '';
    }
    
    // Get character attribute
    public function get_character_attribute($name) {
        $name = strtolower($name);
        return $this->character_data['Attributes'][$name] ?? '';
    }

    public function get_all_state_variables() {
        return $this->state['variables'] ?? [];
    }

    public function get_all_character_attributes() {
        return array_change_key_case($this->character_data['Attributes'] ?? [], CASE_LOWER);
    }


    private function safe_evaluate($condition) {
        $parts = preg_split('/(\&\&|\|\|)/', $condition, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = true;
        $operator = '&&';
    
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part == '&&' || $part == '||') {
                $operator = $part;
            } else {
                $part_result = $this->evaluate_single_condition($part);
                if ($operator == '&&') {
                    $result = $result && $part_result;
                } else {
                    $result = $result || $part_result;
                }
            }
        }
    
        return $result;
    }
    
    // Evaluate a single condition
    private function evaluate_single_condition($condition) {
        // Handle array-like access for quest progress
        if (preg_match('/quest_progress\[\'(.+?)\'\]/', $condition, $matches)) {
            $quest_name = $matches[1];
            $quest_progress = $this->get_all_quest_progress();
            $condition = str_replace("quest_progress['$quest_name']", "'" . ($quest_progress[$quest_name] ?? '') . "'", $condition);
        }
    
        $operators = array(
            '>=', '<=', '!=', '==', '>', '<',
            'is greater than or equal to' => '>=',
            'is less than or equal to' => '<=',
            'is not equal to' => '!=',
            'is equal to' => '==',
            'is greater than' => '>',
            'is less than' => '<'
        );
        foreach ($operators as $text_operator => $symbolic_operator) {
            if (is_string($text_operator)) {
                $condition = str_replace($text_operator, $symbolic_operator, $condition);
            }
        }
        foreach ($operators as $operator) {
            if (strpos($condition, $operator) !== false) {
                list($left, $right) = explode($operator, $condition);
                $left = trim($left);
                $right = trim($right);
                
                $left_value = $this->get_variable($left);
                $right_value = is_numeric($right) ? floatval($right) : $this->get_variable($right);

                error_log("Comparing evaluate_single_condition  {$left_value} {$operator} {$right_value}");

                switch ($operator) {
                    case '>=': return $left_value >= $right_value;
                    case '<=': return $left_value <= $right_value;
                    case '!=': return $left_value != $right_value;
                    case '==': return $left_value == $right_value;
                    case '>': return $left_value > $right_value;
                    case '<': return $left_value < $right_value;
                }
            }
        }
        return false;
    }

     // Get variable value
    private function get_variable($var) {
        if (is_numeric($var)) {
            return floatval($var);
        } elseif (preg_match('/^[\'"].*[\'"]$/', $var)) {
            return trim($var, '\'"');
        } else {
            $context = array_merge(
                $this->get_all_state_variables(),
                $this->get_all_character_attributes(),
                $this->get_all_quest_progress()
            );
            return isset($context[$var]) ? $context[$var] : null;
        }
    }

    // Get all variables
    public function get_all_variables() {
        return $this->state['variables'] ?? [];
    }

    // Get all quest progress
    private function get_all_quest_progress() {
        $quest_progress = get_user_meta($this->user_id, 'quest_progress', true);
        return is_array($quest_progress) ? $quest_progress : array();
    }

    public function update_state($action, $value) {
        // Parse the action and update the state accordingly
        // This is a simplified example and should be expanded based on your needs
        $parts = explode(':', $action);
        if (count($parts) === 2) {
            $type = $parts[0];
            $name = $parts[1];

            switch ($type) {
                case 'set':
                    $this->state['variables'][$name] = intval($value);
                    break;
                case 'increment':
                    $this->state['variables'][$name] = ($this->state['variables'][$name] ?? 0) + intval($value);
                    break;
                case 'decrement':
                    $this->state['variables'][$name] = ($this->state['variables'][$name] ?? 0) - intval($value);
                    break;
                // Add more action types as needed
            }

            $this->save_state();
        }
        // Add debug output
        //error_log("State updated: " . print_r($this->state['variables'], true));
    }
    
    //  Update character attributes
    public function update_character_attribute($attribute, $value) {
        $attribute = strtolower($attribute);
        $this->character_data['Attributes'][$attribute] = $value;
        update_user_meta($this->user_id, 'adventure_game_character', $this->character_data);
    }

    
    // Update inventory
    public function update_inventory($item, $quantity) {
        $this->state['inventory'][$item] = ($this->state['inventory'][$item] ?? 0) + $quantity;
        $this->save_state();
    }

    // Update flags
    public function update_flag($flag, $value) {
        $this->state['flags'][$flag] = $value;
        $this->save_state();
    }

    // Update quest progress
    public function update_quest_progress($quest_name, $status) {
        $quest_progress = $this->get_all_quest_progress();
        $quest_progress[$quest_name] = $status;
        update_user_meta($this->user_id, 'quest_progress', $quest_progress);
    }
    
    // Get quest progress
    public function get_quest_progress($quest) {
        $quest_progress = $this->get_all_quest_progress();
        return isset($quest_progress[$quest]) ? $quest_progress[$quest] : 'Not started';
    }
    // Check path availability
    public function check_path_availability($path_id) {
        // Implement logic to check if a path is available based on state
        // This is a placeholder and should be customized based on your requirements
        return true;
    }

    // Apply choice consequences
    public function apply_choice_consequences($choice_id) {
        // Implement logic to update state based on user choices
        // This is a placeholder and should be customized based on your requirements
        $this->state['choices_made'][] = $choice_id;
        $this->save_state();
    }

    // Process conditional content
    public function process_conditional_content($content) {
        // Implement logic to modify content based on state conditions
        // This is a placeholder and should be customized based on your requirements
        return $content;
    }

    // Add more methods as needed for managing inventory, flags, relationships, stats, etc.
}


function iasb_get_state_manager($user_id, $story_id) {
    static $instances = [];
    $key = $user_id . '_' . $story_id;
    if (!isset($instances[$key])) {
        $instances[$key] = new IASB_State_Manager($user_id, $story_id);
    }
    return $instances[$key];
}