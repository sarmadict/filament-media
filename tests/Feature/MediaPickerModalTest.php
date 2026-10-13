<?php

use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Sarmadict\FilamentMedia\Filament\Forms\Components\MediaPicker;

uses(RefreshDatabase::class);

class MediaPickerModalFormForTesting extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public array $data = [];

    public string $width = '5xl';

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            MediaPicker::make('inherited'),
            MediaPicker::make('normal')->modalWidth('normal'),
            MediaPicker::make('custom')->modalWidth(fn (): string => $this->width),
            MediaPicker::make('inline')->inline()->modalWidth(Width::Screen),
            MediaPicker::make('disabled')->disabled()->modalWidth(Width::Screen),
        ]);
    }

    public function render(): string
    {
        return '<form>{{ $this->form }}</form>';
    }
}

it('renders independent modal widths and updates dynamic field overrides', function (): void {
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    config()->set('filament-media.picker.modal_width', Width::Screen);

    $form = Livewire::test(MediaPickerModalFormForTesting::class);
    $document = new DOMDocument;
    @$document->loadHTML($form->html());
    $xpath = new DOMXPath($document);
    $modals = $xpath->query('//div[@role="dialog"]');
    $panels = $xpath->query('//div[@class="filament-media-picker-modal__panel"]');

    expect($modals)->toHaveCount(3)
        ->and($modals->item(0)->getAttribute('class'))->toContain('filament-media-picker-modal--screen')
        ->and($modals->item(1)->getAttribute('class'))->not->toContain('filament-media-picker-modal--screen')
        ->and($modals->item(2)->getAttribute('class'))->not->toContain('filament-media-picker-modal--screen')
        ->and($panels->item(1)->getAttribute('style'))->toContain('--filament-media-picker-modal-width: 1180px')
        ->and($panels->item(2)->getAttribute('style'))->toContain('--filament-media-picker-modal-width: 64rem');

    @$document->loadHTML($form->set('width', 'screen')->html());
    $modals = (new DOMXPath($document))->query('//div[@role="dialog"]');

    expect($modals->item(1)->getAttribute('class'))->not->toContain('filament-media-picker-modal--screen')
        ->and($modals->item(2)->getAttribute('class'))->toContain('filament-media-picker-modal--screen');
});
