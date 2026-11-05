{{--
    Bantuan rumus KaTeX + pratinjau soal.
    Logika Alpine ada di window.mathHelper (resources/views/filament/math-arabic-scripts.blade.php).
    Komponen ini mencari editor pertanyaan di dalam .fi-section yang sama.
--}}
<div x-data="mathHelper()" wire:ignore class="mp-wrap mp-ignore">

    {{-- Toolbar --}}
    <div class="mp-toolbar">
        <button type="button" class="mp-btn" :class="{ 'is-active': showBuilder }" @click="toggleBuilder()">
            <span style="font-family: serif; font-weight: 700;">∑</span> Sisipkan Rumus
        </button>
        <button type="button" class="mp-btn" :class="{ 'is-active': showPreview }" @click="showPreview = !showPreview; $nextTick(() => renderPreview())">
            <span>👁</span> Pratinjau Tampilan Siswa
        </button>
        <span class="mp-hint">Ketik <code>$rumus$</code> (langsung jadi rumus visual di editor) atau klik <b>∑ Sisipkan Rumus</b></span>
    </div>

    {{-- Panel pembuat rumus (inline, bukan modal agar tidak bentrok dengan modal Filament) --}}
    <div x-show="showBuilder" x-cloak x-transition.opacity class="mp-panel">
        <div class="mp-tabs">
            <template x-for="tab in [
                { key: 'pecahan', label: 'Pecahan a/b' },
                { key: 'akar', label: 'Akar √x' },
                { key: 'pangkat', label: 'Pangkat x²' },
                { key: 'subskrip', label: 'Subskrip x₁' },
                { key: 'custom', label: 'Simbol & Rumus Jadi' },
            ]" :key="tab.key">
                <button type="button" class="mp-btn" :class="{ 'is-active': activeTab === tab.key }"
                        @click="activeTab = tab.key" x-text="tab.label"></button>
            </template>
        </div>

        <div x-show="activeTab === 'pecahan'" class="mp-row">
            <div class="mp-field"><label>Pembilang (atas)</label>
                <input type="text" class="mp-input" x-model="fracNum" @input="renderFormula()"></div>
            <div class="mp-field"><label>Penyebut (bawah)</label>
                <input type="text" class="mp-input" x-model="fracDen" @input="renderFormula()"></div>
        </div>

        <div x-show="activeTab === 'akar'" class="mp-row">
            <div class="mp-field"><label>Isi di dalam akar</label>
                <input type="text" class="mp-input" x-model="sqrtVal" @input="renderFormula()"></div>
        </div>

        <div x-show="activeTab === 'pangkat'" class="mp-row">
            <div class="mp-field"><label>Basis</label>
                <input type="text" class="mp-input" x-model="powBase" @input="renderFormula()"></div>
            <div class="mp-field"><label>Pangkat</label>
                <input type="text" class="mp-input" x-model="powExp" @input="renderFormula()"></div>
        </div>

        <div x-show="activeTab === 'subskrip'" class="mp-row">
            <div class="mp-field"><label>Huruf dasar</label>
                <input type="text" class="mp-input" x-model="subBase" @input="renderFormula()"></div>
            <div class="mp-field"><label>Indeks bawah</label>
                <input type="text" class="mp-input" x-model="subVal" @input="renderFormula()"></div>
        </div>

        <div x-show="activeTab === 'custom'" style="display: flex; flex-direction: column; gap: .6rem;">
            <div>
                <span class="mp-label">Simbol</span>
                <div class="mp-toolbar">
                    <template x-for="sym in symbols" :key="sym">
                        <button type="button" class="mp-btn mp-btn-sm" @click="addSymbol(sym)" x-text="sym.replace('\\', '')"></button>
                    </template>
                </div>
            </div>
            <div>
                <span class="mp-label">Rumus jadi</span>
                <div class="mp-toolbar">
                    <template x-for="p in presets" :key="p.label">
                        <button type="button" class="mp-btn" @click="setPreset(p.value)" x-text="p.label"></button>
                    </template>
                </div>
            </div>
            <div class="mp-field">
                <label>Kode rumus (LaTeX)</label>
                <input type="text" class="mp-input mp-mono" x-model="customFormula" @input="renderFormula()" placeholder="contoh: \frac{a}{b} + \sqrt{c}">
            </div>
        </div>

        <div>
            <span class="mp-label">Hasil</span>
            <div x-ref="formula" class="mp-formula"></div>
        </div>

        <div class="mp-actions">
            <button type="button" class="mp-btn" @click="showBuilder = false">Batal</button>
            <button type="button" class="mp-btn mp-btn-primary" @click="insertFormula()">✓ Sisipkan ke Pertanyaan</button>
        </div>
    </div>

    {{-- Pratinjau tampilan siswa --}}
    <div x-show="showPreview" class="mp-preview">
        <div class="mp-preview-title">Pratinjau tampilan siswa</div>
        <div x-ref="preview" class="mp-preview-body"></div>
    </div>
</div>
