@props([
    'name',
    'accept' => 'image/jpeg,image/png,application/pdf',
    'maxKb' => 2048,
    'required' => false,
    'hint' => null,
    'id' => null,
])

@php
    $inputId = $id ?? 'upload-'.$name;
    $hasError = $errors->has($name);
@endphp

{{--
    Dropzone with a client-side preview, styled after the panel uploader.

    The real <input type="file"> is not hidden with `display:none` — it is laid
    transparently over the whole box. That keeps native click-to-browse and
    drag-and-drop working without JS, and keeps a `required` input focusable so
    the browser can point at it instead of silently blocking submit.
--}}
<div
    x-data="{
        file: null,
        preview: null,
        tooBig: false,
        maxKb: {{ (int) $maxKb }},
        choose(event) {
            this.releasePreview();

            const picked = event.target.files[0];

            if (! picked) {
                this.file = null;
                this.tooBig = false;

                return;
            }

            this.file = {
                name: picked.name,
                size: picked.size,
                isImage: picked.type.startsWith('image/'),
            };
            this.tooBig = picked.size > this.maxKb * 1024;

            if (this.file.isImage) {
                this.preview = URL.createObjectURL(picked);
            }
        },
        clear() {
            this.$refs.input.value = '';
            this.releasePreview();
            this.file = null;
            this.tooBig = false;
        },
        releasePreview() {
            if (this.preview) {
                URL.revokeObjectURL(this.preview);
                this.preview = null;
            }
        },
        get sizeLabel() {
            if (! this.file) {
                return '';
            }

            const kb = this.file.size / 1024;

            return kb >= 1024
                ? (kb / 1024).toFixed(1) + ' MB'
                : Math.max(1, Math.round(kb)) + ' KB';
        },
    }"
    x-on:beforeunload.window="releasePreview()"
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    <div class="relative rounded-xl border-2 border-dashed transition-colors"
         :class="tooBig ? 'border-red-400 bg-red-50' : (file ? 'border-amber-400 bg-amber-50/60' : 'border-(--border) hover:border-amber-400')"
         @class(['border-red-400' => $hasError])>

        <input type="file"
               id="{{ $inputId }}"
               name="{{ $name }}"
               accept="{{ $accept }}"
               @if($required) required @endif
               x-ref="input"
               x-on:change="choose($event)"
               class="absolute inset-0 z-0 w-full h-full opacity-0 cursor-pointer"
               aria-describedby="{{ $inputId }}-hint">

        {{-- Empty state. Rendered by default so there is no flash before Alpine boots. --}}
        <div x-show="! file" class="pointer-events-none flex flex-col items-center justify-center gap-2 px-6 py-8 text-center">
            <div class="w-11 h-11 rounded-full bg-amber-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <p class="text-sm font-semibold" style="color:var(--text)">
                Klik untuk memilih berkas <span class="font-normal" style="color:var(--muted)">atau seret ke sini</span>
            </p>
            @if($hint)
                <p id="{{ $inputId }}-hint" class="text-xs" style="color:var(--muted)">{{ $hint }}</p>
            @endif
        </div>

        {{-- Selected state. Hidden pre-Alpine so it never flashes empty. --}}
        <div x-show="file" style="display:none" class="pointer-events-none flex items-center gap-4 p-4">
            <template x-if="preview">
                <img :src="preview" alt="Pratinjau berkas terpilih"
                     class="w-20 h-20 rounded-lg object-cover border shrink-0" style="border-color:var(--border)">
            </template>

            <template x-if="! preview">
                <div class="w-20 h-20 rounded-lg border flex flex-col items-center justify-center gap-1 shrink-0"
                     style="border-color:var(--border); background:var(--card)">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-[10px] font-bold" style="color:var(--muted)">PDF</span>
                </div>
            </template>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold truncate" style="color:var(--text)" x-text="file?.name"></p>
                <p class="text-xs mt-0.5" style="color:var(--muted)" x-text="sizeLabel"></p>
                <p x-show="tooBig" class="text-xs mt-1 font-semibold text-red-600">
                    Ukuran melebihi batas. Pilih berkas yang lebih kecil.
                </p>
                <p x-show="! tooBig" class="text-xs mt-1 font-semibold text-amber-700">Klik untuk mengganti berkas</p>
            </div>

            <button type="button"
                    x-on:click.stop="clear()"
                    class="pointer-events-auto relative z-10 shrink-0 w-8 h-8 rounded-lg border flex items-center justify-center transition-colors hover:bg-red-50 hover:border-red-300 hover:text-red-600"
                    style="border-color:var(--border); color:var(--muted)"
                    aria-label="Hapus berkas terpilih">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

</div>
