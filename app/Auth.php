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

namespace Fisharebest\Webtrees;

use stdClass;

/**
 * Authentication.
 */
class Auth
{
    // Privacy constants
    public const PRIV_PRIVATE = 2; // Allows visitors to view the item
    public const PRIV_USER    = 1; // Allows members to access the item
    public const PRIV_NONE    = 0; // Allows managers to access the item
    public const PRIV_HIDE    = -1; // Hide the item to all users

    /**
     * Are we currently logged in?
     *
     * @return bool
     */
    public static function check(): bool
    {
        return self::id() !== null;
    }

    /**
     * Is the specified/current user an administrator?
     *
     * @param User|null $user
     *
     * @return bool
     */
    public static function isAdmin(User $user = null): bool
    {
        $user = $user ?? self::user();

        return $user->getPreference('canadmin') === '1';
    }

    /**
     * Is the specified/current user a manager of a tree?
     *
     * @param Tree      $tree
     * @param User|null $user
     *
     * @return bool
     */
    public static function isManager(Tree $tree, User $user = null): bool
    {
        $user = $user ?? self::user();

        return self::isAdmin($user) || $tree->getUserPreference($user, 'canedit') === 'admin';
    }

    /**
     * Is the specified/current user a moderator of a tree?
     *
     * @param Tree      $tree
     * @param User|null $user
     *
     * @return bool
     */
    public static function isModerator(Tree $tree, User $user = null): bool
    {
        $user = $user ?? self::user();

        return self::isManager($tree, $user) || $tree->getUserPreference($user, 'canedit') === 'accept';
    }

    /**
     * Is the specified/current user an editor of a tree?
     *
     * @param Tree      $tree
     * @param User|null $user
     *
     * @return bool
     */
    public static function isEditor(Tree $tree, User $user = null): bool
    {
        $user = $user ?? self::user();

        return self::isModerator($tree, $user) || $tree->getUserPreference($user, 'canedit') === 'edit';
    }

    /**
     * Is the specified/current user a member of a tree?
     *
     * @param Tree      $tree
     * @param User|null $user
     *
     * @return bool
     */
    public static function isMember(Tree $tree, User $user = null): bool
    {
        $user = $user ?? self::user();

        return self::isEditor($tree, $user) || $tree->getUserPreference($user, 'canedit') === 'access';
    }

    /**
     * What is the specified/current user's access level within a tree?
     *
     * @param Tree      $tree
     * @param User|null $user
     *
     * @return int
     */
    public static function accessLevel(Tree $tree, User $user = null)
    {
        $user = $user ?? self::user();

        if (self::isManager($tree, $user)) {
            return self::PRIV_NONE;
        }

        if (self::isMember($tree, $user)) {
            return self::PRIV_USER;
        }

        return self::PRIV_PRIVATE;
    }

    /**
     * The ID of the authenticated user, from the current session.
     *
     * @return int|null
     */
    public static function id()
    {
        return Session::get('wt_user');
    }

    /**
     * The authenticated user, from the current session.
     *
     * @return User
     */
    public static function user()
    {
        return User::find(self::id()) ?? User::visitor();
    }

    /**
     * Login directly as an explicit user - for masquerading.
     *
     * @param User $user
     *
     * @return void
     */
    public static function login(User $user)
    {
        Session::regenerate(false);
        Session::put('wt_user', $user->getUserId());
    }

    /**
     * End the session for the current user.
     *
     * @return void
     */
    public static function logout()
    {
        Session::regenerate(true);
    }
}
