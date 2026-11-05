<?php

namespace App\Filament\Plugins;

use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;

class MathematicsPlugin implements RichContentPlugin
{
    public static function make(): static
    {
        return new static();
    }

    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [
            asset('js/filament/tiptap-inline-math.js'),
        ];
    }

    public function getEditorTools(): array
    {
        return [];
    }

    public function getEditorActions(): array
    {
        return [];
    }
}

