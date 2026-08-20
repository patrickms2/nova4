<?php

namespace Voodflow\Voodflow\Nodes\Target;

use Voodflow\Voodflow\Contracts\NodeInterface;
use Voodflow\Voodflow\Execution\ExecutionContext;
use Voodflow\Voodflow\Execution\ExecutionResult;

/**
 * Target
 * 
 * Custom node for workflow automation
 * 
 * All metadata (name, description, author, version, color, icon, etc.) 
 * is defined in manifest.json to avoid duplication.
 * 
 * @author Voodflow
 * @version 1.0.0
 */
class Target implements NodeInterface
{
    public static function type(): string
    {
        return 'target';
    }
    
    public static function defaultConfig(): array
    {
        return [
            'label' => 'Target',
            'description' => '',
            // Add your configuration fields here
        ];
    }
    
    /**
     * Execute the node logic
     */
    public function execute(ExecutionContext $context): ExecutionResult
    {
        // TODO: Implement your node logic here
        
        // Get input data from previous node
        $inputData = $context->input;
        
        // Get configuration
        // $config = $context->getConfig('field_name', 'default');
        
        // Process and return output
        return ExecutionResult::success($inputData);
        
        // For nodes with multiple outputs:
        // return ExecutionResult::success($data)->toOutput('handle_id');
    }
    
    /**
     * Validate node configuration
     */
    public function validate(array $config): array
    {
        $errors = [];
        
        // TODO: Add validation logic
        
        return $errors;
    }
    
    /**
     * Get node definition (UI configuration fields)
     */
    public static function definition(): array
    {
        return [
            // Define configuration fields here
            // Example:
            // ['key' => 'field_name', 'type' => 'text', 'label' => 'Field Label', 'required' => true]
        ];
    }
    
    /**
     * Whether this node supports retry on failure
     */
    public static function supportsRetry(): bool
    {
        return true; // Change to false if retry is not supported
    }
}
