<?php

namespace App\Enums;

enum Role: string
{
    case FrontDesk = 'front_desk';
    case Nurse = 'nurse';
    case QualityManager = 'quality_manager';

    public function label(): string
    {
        return match ($this) {
            self::FrontDesk => 'Front Desk',
            self::Nurse => 'Nurse',
            self::QualityManager => 'Quality Manager',
        };
    }

    /** Where each role lands after login. */
    public function homeRoute(): string
    {
        return match ($this) {
            self::FrontDesk => 'patients.index',
            self::Nurse => 'visits.queue',
            self::QualityManager => 'dashboard',
        };
    }
}
