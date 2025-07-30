<?php
namespace Doubleedesign\Comet\Core;

enum ThemeColor: string {
    case PRIMARY = 'primary';
    case SECONDARY = 'secondary';
    case ACCENT = 'accent';
    case ERROR = 'error';

    case SUCCESS = 'success';
    case WARNING = 'warning';
    case INFO = 'info';
    case LIGHT = 'light';
    case DARK = 'dark';
    case WHITE = 'white';
}
