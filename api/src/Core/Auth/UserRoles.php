<?php

namespace App\Core\Auth;

/**
 * List of user roles.
 */
abstract class UserRoles
{
    /**
     * None.
     *
     * The user cannot access this part of the application.
     */
    public const NONE = 0;

    /**
     * View only.
     *
     * The user can view the resources of this part of the application but cannot edit them.  
     * The user can access parts of the application of type "Access/No access".
     */
    public const ACCESS = 1;

    /**
     * Edit allowed.
     *
     * The user can view and edit the resources of this part of the application.
     */
    public const EDIT = 2;
}
