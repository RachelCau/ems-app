<?php

namespace App\Filament\Utilities;

use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;

class FeatureNavigation
{
    /**
     * Create a navigation item
     *
     * @param string|callable $getLabel - The navigation label or a callable that returns it
     * @param array $params - Additional parameters for NavigationItem::make()
     * @return NavigationItem
     */
    public static function createItem($getLabel, array $params = []): NavigationItem
    {
        // Check if the item is part of a group - if so, don't add an icon
        $hasGroup = isset($params['group']) && !empty($params['group']);

        // Create the navigation item
        $item = NavigationItem::make($getLabel)
            ->url($params['url'] ?? null)
            ->isActiveWhen($params['isActiveWhen'] ?? null)
            ->group($params['group'] ?? null);
            
        // Only add icon if not in a group
        if (!$hasGroup && isset($params['icon'])) {
            $item->icon($params['icon']);
        }
            
        // Add badge if specified
        if (isset($params['badge'])) {
            $item->badge($params['badge']);
        }
            
        return $item;
    }
    
    /**
     * Create a navigation group
     *
     * @param string|callable $getLabel - The navigation label or a callable that returns it
     * @param array $items - The navigation items
     * @param array $params - Additional parameters for NavigationGroup::make()
     * @return NavigationGroup|null
     */
    public static function createGroup($getLabel, array $items, array $params = []): ?NavigationGroup
    {
        // Filter out null items
        $filteredItems = array_filter($items);
        
        // If no items, return null
        if (empty($filteredItems)) {
            return null;
        }
        
        // Create the navigation group with the visible items
        return NavigationGroup::make($getLabel)
            ->items($filteredItems)
            ->icon($params['icon'] ?? null)
            ->collapsible($params['collapsible'] ?? true);
    }
} 