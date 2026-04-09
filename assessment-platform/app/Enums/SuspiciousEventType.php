<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabelAndValues;

enum SuspiciousEventType: string
{
    use HasLabelAndValues;

    case FullscreenExit = 'fullscreen_exit';
    case TabSwitch = 'tab_switch';
    case WindowBlur = 'window_blur';
    case CopyAttempt = 'copy_attempt';
    case PasteAttempt = 'paste_attempt';
    case RightClick = 'right_click';
    case CameraDenied = 'camera_denied';
    case FaceNotDetected = 'face_not_detected';

    public function label(): string
    {
        return match ($this) {
            self::FullscreenExit => 'Fullscreen exit',
            self::TabSwitch => 'Tab switch',
            self::WindowBlur => 'Window blur',
            self::CopyAttempt => 'Copy attempt',
            self::PasteAttempt => 'Paste attempt',
            self::RightClick => 'Right click',
            self::CameraDenied => 'Camera denied',
            self::FaceNotDetected => 'Face not detected',
        };
    }
}
