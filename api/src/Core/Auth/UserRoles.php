<?php

namespace App\Core\Auth;

/**
 * List of user roles.
 */
enum UserRoles: int
{
    /**
     * None.
     *
     * The user cannot access this part of the application.
     */
    case NONE = 0;

    /**
     * View only.
     *
     * The user can view the resources of this part of the application but cannot edit them.  
     * The user can access parts of the application of type "Access/No access".
     */
    case ACCESS = 1;

    /**
     * Edit allowed.
     *
     * The user can view and edit the resources of this part of the application.
     */
    case EDIT = 2;
}
