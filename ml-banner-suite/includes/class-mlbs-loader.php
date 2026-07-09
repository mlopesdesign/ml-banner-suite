<?php
/**
 * Hook loader.
 *
 * @package MLBannerSuite
 */

if (! defined('ABSPATH')) {
    exit;
}

class MLBS_Loader
{
    /**
     * Action hooks.
     *
     * @var array<int, array<string, mixed>>
     */
    protected $actions = array();

    /**
     * Filter hooks.
     *
     * @var array<int, array<string, mixed>>
     */
    protected $filters = array();

    /**
     * Register action.
     *
     * @param string $hook Hook name.
     * @param object $component Component instance.
     * @param string $callback Method.
     * @param int    $priority Priority.
     * @param int    $accepted_args Accepted args.
     * @return void
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions[] = compact('hook', 'component', 'callback', 'priority', 'accepted_args');
    }

    /**
     * Register filter.
     *
     * @param string $hook Hook name.
     * @param object $component Component instance.
     * @param string $callback Method.
     * @param int    $priority Priority.
     * @param int    $accepted_args Accepted args.
     * @return void
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters[] = compact('hook', 'component', 'callback', 'priority', 'accepted_args');
    }

    /**
     * Run all hooks.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}
