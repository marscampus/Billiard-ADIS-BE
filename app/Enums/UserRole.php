<?php

namespace App\Enums;

enum UserRole: string {
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
    case TECHNICIAN = 'technician';

    public function label(): string {
        return match ($this) {
            UserRole::ADMIN => __('Admin'),
            UserRole::CUSTOMER => __('Customer'),
            UserRole::TECHNICIAN => __('Technician'),
        };
    }

    public function isCustomer(): bool {
        return $this === UserRole::CUSTOMER;
    }

    public function isAdmin(): bool {
        return $this === UserRole::ADMIN;
    }

    public function isTechnician(): bool {
        return $this === UserRole::TECHNICIAN;
    }
}