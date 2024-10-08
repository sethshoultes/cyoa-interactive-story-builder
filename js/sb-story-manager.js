jQuery(document).ready(function($) {
    console.log('Fart Story Manager JS Loaded');

    // Fetch the story structure via AJAX
    $.ajax({
        url: iasb_ajax_object.ajaxurl, // AJAX URL
        method: 'POST',
        data: {
            action: 'iasb_get_story_structure', // The AJAX action
            nonce: iasb_ajax_object.nonce // Security nonce
        },
        success: function(response) {
            if (response.success) {
                console.log('Story Structure:', response.data); // Log the story structure
                renderStoryTree(response.data); // Pass the data to render the tree
            } else {
                alert('Error fetching story structure: ' + response.data);
            }
        },
        error: function() {
            alert('An error occurred while fetching the story structure.');
        }
    });

    // Function to render the story tree using D3.js
    function renderStoryTree(data) {
        const width = 960;
        const height = 600;
        
        // Clear the previous SVG content if it exists
        d3.select("#story-manager-tree").selectAll("*").remove();

        const svg = d3.select('#story-manager-tree')
            .append('svg')
            .attr('width', width)
            .attr('height', height);

        const treeLayout = d3.tree().size([width - 40, height - 40]);

        // Convert the flat array into a hierarchical structure (D3 requires hierarchical data)
        const root = d3.hierarchy({ name: "Root", children: data }, d => d.children);

        // Layout the tree
        treeLayout(root);

        // Draw links (lines between nodes)
        svg.selectAll('line')
            .data(root.links())
            .enter()
            .append('line')
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y)
            .attr('stroke', '#ccc');

        // Draw nodes (circles representing universes, branches, endings)
        svg.selectAll('circle')
            .data(root.descendants())
            .enter()
            .append('circle')
            .attr('cx', d => d.x)
            .attr('cy', d => d.y)
            .attr('r', 5)
            .attr('fill', '#69b3a2');

        // Add text labels (node names)
        svg.selectAll('text')
            .data(root.descendants())
            .enter()
            .append('text')
            .attr('x', d => d.x + 10)
            .attr('y', d => d.y + 5)
            .text(d => d.data.name || "Unknown");
    }
});
