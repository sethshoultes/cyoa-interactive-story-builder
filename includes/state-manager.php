<?php
// app/public/wp-content/plugins/cyoa-interactive-story-builder/includes/state-manager.php

class IASB_State_Manager {
    private $user_id;
    private $story_id;
    private $state;
    private $character_data;
    //private $character_id;
    

    public function __construct($user_id, $story_id, $character_id) {
        $this->user_id = $user_id;
        $this->story_id = $story_id;
        $this->state = $this->get_story_state();
        $this->character_data = $this->get_character_data();
       // $this->character_id = $character_id;
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
        $state = array();
        if (is_user_logged_in()) {
            $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
            $state = $user_state[$this->story_id] ?? array();
        } else {
            $state = isset($_SESSION['iasb_user_state'][$this->story_id]) ? $_SESSION['iasb_user_state'][$this->story_id] : array();
        }
        
        if (empty($state)) {
            $state = $this->initialize_state();
        }
        
        return $state;
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

    public function save_state($state) {
        $saved = false;
        if (is_user_logged_in()) {
            $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
            $user_state[$this->story_id] = $state;
            $saved = update_user_meta($this->user_id, 'iasb_user_state', $user_state);
        } else {
            $_SESSION['iasb_user_state'][$this->story_id] = $state;
            $saved = true;
        }
        return $saved;
    }

    public function get_inventory() {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        return isset($user_state['global_inventory']) ? $user_state['global_inventory'] : array();
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

       // error_log("Evaluation context in public function evaluate_complex_condition--> " . print_r($context, true));

        // Use safe_evaluate instead of eval
        $result = $this->safe_evaluate($condition);

        if ($result === false) {
            //error_log('Failed to evaluate condition in public function evaluate_complex_condition--> ' . $condition);
            return false;
        }

        //error_log("Condition result in public function evaluate_complex_condition--> " . ($result ? 'true' : 'false'));
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
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        return isset($user_state['variables']) ? $user_state['variables'] : array();
    }
    public function update_state_variable($name, $value) {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        if (!isset($user_state['variables'])) {
            $user_state['variables'] = array();
        }
        $user_state['variables'][$name] = $value;
        update_user_meta($this->user_id, 'iasb_user_state', $user_state);
    }

    public function get_all_character_attributes() {
        return array_change_key_case($this->character_data['Attributes'] ?? [], CASE_LOWER);
    }

    public function add_to_inventory($item, $quantity = 1) {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        
        if (!isset($user_state['global_inventory'])) {
            $user_state['global_inventory'] = array();
        }
        
        if (!isset($user_state['global_inventory'][$item])) {
            $user_state['global_inventory'][$item] = 0;
        }
        $user_state['global_inventory'][$item] += intval($quantity);
        
        update_user_meta($this->user_id, 'iasb_user_state', $user_state);
    }

    public function remove_from_inventory($item, $quantity = 1) {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        
        if (isset($user_state['global_inventory'][$item])) {
            $user_state['global_inventory'][$item] = max(0, $user_state['global_inventory'][$item] - intval($quantity));
            if ($user_state['global_inventory'][$item] == 0) {
                unset($user_state['global_inventory'][$item]);
            }
            update_user_meta($this->user_id, 'iasb_user_state', $user_state);
        }
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

                //error_log("Comparing evaluate_single_condition  {$left_value} {$operator} {$right_value}");

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
    public function get_all_quest_progress() {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        return isset($user_state['quests']) ? $user_state['quests'] : array();
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

            $this->save_state($this->state);
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
    public function update_inventory($item, $quantity = 1, $operation = 'add') {
        $user_id = get_current_user_id();
        $inventory = get_user_meta($user_id, 'iasb_inventory', true);
        if (!is_array($inventory)) {
            $inventory = array();
        }

        if ($operation === 'add') {
            $inventory[$item] = ($inventory[$item] ?? 0) + $quantity;
        } elseif ($operation === 'remove') {
            $inventory[$item] = max(0, ($inventory[$item] ?? 0) - $quantity);
            if ($inventory[$item] == 0) {
                unset($inventory[$item]);
            }
        }

        update_user_meta($user_id, 'iasb_inventory', $inventory);
    }
    public function add_to_global_inventory($item, $quantity = 1) {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true);
        
        if (!is_array($user_state)) {
            $user_state = array();
        }
        
        if (!isset($user_state['global_inventory'])) {
            $user_state['global_inventory'] = array();
        }
        
        if (!isset($user_state['global_inventory'][$item])) {
            $user_state['global_inventory'][$item] = 0;
        }
        
        $user_state['global_inventory'][$item] += $quantity;
        
        update_user_meta($this->user_id, 'iasb_user_state', $user_state);
    }

    // Update flags
    public function update_flag($flag, $value) {
        $this->state['flags'][$flag] = $value;
        $this->save_state($this->state);
    }

    // Update quest progress
    public function update_quest_progress($quest_id, $progress) {
        $user_state = get_user_meta($this->user_id, 'iasb_user_state', true) ?: array();
        if (!isset($user_state['quests'])) {
            $user_state['quests'] = array();
        }
        $user_state['quests'][$quest_id] = $progress;
        update_user_meta($this->user_id, 'iasb_user_state', $user_state);
    }
    
    // Get quest progress
    // Get quest progress
    public function get_quest_progress($quest_id) {
        $quest_progress = $this->state['quests'] ?? [];

        if (is_array($quest_progress)) {
            if (is_string($quest_id)) {
                return isset($quest_progress[$quest_id]) ? $quest_progress[$quest_id] : 'Not started';
            } else {
                return 'Error: Quest ID must be a string';
            }
        } else {
            return 'Error: Quest progress is not an array';
        }
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
        $this->save_state($this->state);
    }

    // Process conditional content
    public function process_conditional_content($content) {
        // Implement logic to modify content based on state conditions
        // This is a placeholder and should be customized based on your requirements
        return $content;
    }

    public function get_character_state() {
        $state = array(
            'inventory' => $this->state['inventory'] ?? [],
            'quests' => $this->get_all_quest_progress(),
            // Add other relevant state data here
        );
        //error_log('Character state in get_character_state: ' . print_r($state, true));
        return $state;
    }

    public function debug_state() {
        $raw_state = get_user_meta($this->user_id, 'iasb_story_state_' . $this->story_id, true);
        //error_log("Debug - Raw state from database: " . print_r($raw_state, true));
        $parsed_state = maybe_unserialize($raw_state);
        //error_log("Debug - Parsed state: " . print_r($parsed_state, true));
    }

    // Add more methods as needed for managing inventory, flags, relationships, stats, etc.
}

/**
 * Displays character profile data (inventory, quests, etc.) on the character profile page.
 *
 * This function assumes that it is called on the character profile page, and that the character ID is accessible via get_the_ID().
 * It also assumes that the associated story ID is stored in a post meta field called "associated_story".
 *
 * @since 1.0.0
 */
function iasb_display_character_profile_data() {
   // error_log('iasb_display_character_profile_data function called');
    $character_id = get_the_ID();
    $user_id = get_current_user_id();
    $user_state = get_user_meta($user_id, 'iasb_user_state', true) ?: array();
    $story_id = get_post_meta($character_id, 'associated_story', true);
    //error_log("User ID: $user_id, Story ID: $story_id, Character ID: $character_id");

    $state_manager = new IASB_State_Manager($user_id, $story_id, $character_id);
    $character_state = $state_manager->get_character_state();
   // error_log('Character state: ' . print_r($character_state, true));
    
    echo '<h2>Character Data</h2>';

    // Display Inventory section
    echo '<h3>Inventory</h3>';

    $global_inventory = isset($user_state['global_inventory']) ? $user_state['global_inventory'] : array();

    if (is_array($global_inventory) && !empty($global_inventory)) {
        echo '<ul>';
        foreach ($global_inventory as $item => $quantity) {
            if (is_array($quantity)) {
                $total_quantity = array_sum($quantity);
                echo "<li>" . esc_html($item) . ": " . esc_html($total_quantity) . "</li>";
            } else {
                echo "<li>" . esc_html($item) . ": " . esc_html($quantity) . "</li>";
            }
        }
        echo '</ul>';
    } else {
        echo '<p>Your inventory is empty.</p>';
    }
    
    // Display Character Stats
    echo '<h3>Character Stats</h3>';
    if (isset($user_state['variables']) && is_array($user_state['variables']) && !empty($user_state['variables'])) {
        echo '<ul>';
        foreach ($user_state['variables'] as $stat => $value) {
            echo "<li>" . ucfirst(esc_html($stat)) . ": " . esc_html($value) . "</li>";
        }
        echo '</ul>';
    } else {
        echo '<p>No character stats available.</p>';
    }

    // Display Quests
    echo '<h3>Quests</h3>';
    if (isset($user_state['quests']) && is_array($user_state['quests']) && !empty($user_state['quests'])) {
        echo '<ul>';
        foreach ($user_state['quests'] as $quest => $status) {
            echo "<li>" . ucfirst(esc_html($quest)) . ": " . esc_html($status) . "</li>";
        }
        echo '</ul>';
    } else {
        echo '<p>No active quests.</p>';
    }

    
    // Display Other State Data
    echo '<h3>Other Information</h3>';
    $other_keys = ['inventory', 'variables', 'strength', 'quests'];
    $other_data = array_diff_key($character_state, array_flip($other_keys));
    if (!empty($other_data)) {
        foreach ($other_data as $key => $value) {
            if (is_array($value) && !empty($value)) {
                echo "<h4>" . ucfirst($key) . "</h4><ul>";
                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        echo "<li>$subkey: " . json_encode($subvalue) . "</li>";
                    } else {
                        echo "<li>$subkey: $subvalue</li>";
                    }
                }
                echo "</ul>";
            } elseif (!is_array($value) && !empty($value)) {
                echo "<p><strong>" . ucfirst($key) . ":</strong> $value</p>";
            }
        }
    } else {
        echo '<p>No additional information available.</p>';
    }
    $state_manager->debug_state();
}

// Hook this function to display on the character profile
add_action('iasb_character_profile', 'iasb_display_character_profile_data');




function iasb_get_state_manager($user_id, $story_id, $character_id) {
    static $instances = [];
    $key = $user_id . '_' . $story_id;
    if (!isset($instances[$key])) {
        $instances[$key] = new IASB_State_Manager($user_id, $story_id, $character_id);
    }
    return $instances[$key];
}