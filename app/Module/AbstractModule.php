<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Tree;
use Illuminate\Database\Capsule\Manager as DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AbstractModule - common functions for blocks
 */
abstract class AbstractModule
{
    /** @var string The directory where the module is installed */
    private $directory;

    /** @var string[] A cached copy of the module settings */
    private $settings;

    /** @var string For custom modules - optional (recommended) version number */
    public const CUSTOM_VERSION = '';

    /** @var string For custom modules - link for support, upgrades, etc. */
    public const CUSTOM_WEBSITE = '';

    protected $layout = 'layouts/default';

    /**
     * Create a new module.
     *
     * @param string $directory Where is this module installed
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Get a block setting.
     *
     * @param int    $block_id
     * @param string $setting_name
     * @param string $default
     *
     * @return string
     */
    public function getBlockSetting(int $block_id, string $setting_name, string $default = ''): string
    {
        $settings = app('cache.array')->rememberForever('block_setting' . $block_id, function () use ($block_id) {
            return DB::table('block_setting')
                ->where('block_id', '=', $block_id)
                ->pluck('setting_value', 'setting_name')
                ->all();
        });

        return $settings[$setting_name] ?? $default;
    }

    /**
     * Set a block setting.
     *
     * @param int    $block_id
     * @param string $setting_name
     * @param string $setting_value
     *
     * @return $this
     */
    public function setBlockSetting(int $block_id, string $setting_name, string $setting_value): self
    {
        DB::table('block_setting')->updateOrInsert([
            'block_id'      => $block_id,
            'setting_name'  => $setting_name,
        ], [
            'setting_value' => $setting_value,
        ]);

        return $this;
    }

    /**
     * What is the default access level for this module?
     *
     * Some modules are aimed at admins or managers, and are not generally shown to users.
     *
     * @return int
     */
    public function defaultAccessLevel(): int
    {
        // Returns one of: Auth::PRIV_HIDE, Auth::PRIV_PRIVATE, Auth::PRIV_USER, WT_PRIV_ADMIN
        return Auth::PRIV_PRIVATE;
    }

    /**
     * Provide a unique internal name for this module
     *
     * @return string
     */
    public function getName(): string
    {
        return basename($this->directory);
    }

    /**
     * Load all the settings for the module into a cache.
     *
     * Since modules may have many settings, and will probably want to use
     * lots of them, load them all at once and cache them.
     *
     * @return void
     */
    private function loadAllSettings()
    {
        if ($this->settings === null) {
            $this->settings = DB::table('module_setting')
                ->where('module_name', '=', $this->getName())
                ->pluck('setting_value', 'setting_name')
                ->all();
        }
    }

    /**
     * Get a module setting. Return a default if the setting is not set.
     *
     * @param string $setting_name
     * @param string $default
     *
     * @return string
     */
    public function getPreference($setting_name, $default = '')
    {
        $this->loadAllSettings();

        if (array_key_exists($setting_name, $this->settings)) {
            return $this->settings[$setting_name];
        }

        return $default;
    }

    /**
     * Set a module setting.
     *
     * Since module settings are NOT NULL, setting a value to NULL will cause
     * it to be deleted.
     *
     * @param string $setting_name
     * @param string $setting_value
     *
     * @return $this
     */
    public function setPreference($setting_name, $setting_value): self
    {
        $this->loadAllSettings();

        DB::table('module_setting')->updateOrInsert([
            'module_name'  => $this->getName(),
            'setting_name' => $setting_name,
        ], [
            'setting_value' => $setting_value,
        ]);

        $this->settings[$setting_name] = $setting_value;

        return $this;
    }

    /**
     * Get a the current access level for a module
     *
     * @param Tree   $tree
     * @param string $component tab, block, menu, etc
     *
     * @return int
     */
    public function getAccessLevel(Tree $tree, $component)
    {
        $access_level = DB::table('module_privacy')
            ->where('gedcom_id', '=', $tree->id())
            ->where('module_name', '=', $this->getName())
            ->where('component', '=', $component)
            ->value('access_level');

        if ($access_level === null) {
            return $this->defaultAccessLevel();
        }

        return (int) $access_level;
    }

    /**
     * Create a response object from a view.
     *
     * @param string  $view_name
     * @param mixed[] $view_data
     * @param int     $status
     *
     * @return Response
     */
    protected function viewResponse($view_name, $view_data, $status = Response::HTTP_OK): Response
    {
        // Make the view's data available to the layout.
        $layout_data = $view_data;

        // Render the view
        $layout_data['content'] = view($view_name, $view_data);

        // Insert the view into the layout
        $html = view($this->layout, $layout_data);

        return new Response($html, $status);
    }
}
