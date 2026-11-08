{{-- KaTeX Lokal (Offline-Ready) & Google Font Non-Blocking --}}
<link rel="stylesheet" href="{{ asset('vendor/katex/katex.min.css') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">

<style>
    /* Arab: Font Amiri & line-height proporsional agar harakat rapi dan terbaca jelas */
    .font-arabic, .text-arabic, .arabic-mode {
        font-family: 'Amiri', 'Traditional Arabic', 'Noto Naskh Arabic', serif !important;
        font-size: 1.35rem !important;
        line-height: 2.3rem !important;
    }

    /* Fallback font umum untuk editor: karakter Arab otomatis menggunakan Amiri */
    .ProseMirror, .fi-prose, .math-output {
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Amiri", "Traditional Arabic", sans-serif;
    }
    .ProseMirror p, .ProseMirror h2, .ProseMirror h3 {
        line-height: 2.1 !important;
    }

    .katex-display {
        margin: 0.6em 0 !important;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .katex {
        font-size: 1.15em !important;
    }
    /* SVG sizing fix agar tidak membesar jika utility Tailwind belum ter-generate */
    svg.w-3, svg.h-3, svg[class*="w-3"] { width: 12px !important; height: 12px !important; min-width: 12px !important; max-width: 12px !important; }
    svg.w-3\.5, svg.h-3\.5, svg[class*="w-3.5"] { width: 14px !important; height: 14px !important; min-width: 14px !important; max-width: 14px !important; }
    svg.w-4, svg.h-4, svg[class*="w-4"] { width: 16px !important; height: 16px !important; min-width: 16px !important; max-width: 16px !important; }
    svg.w-5, svg.h-5, svg[class*="w-5"] { width: 20px !important; height: 20px !important; min-width: 20px !important; max-width: 20px !important; }
    svg.w-6, svg.h-6, svg[class*="w-6"] { width: 24px !important; height: 24px !important; min-width: 24px !important; max-width: 24px !important; }
    svg.w-8, svg.h-8, svg[class*="w-8"] { width: 32px !important; height: 32px !important; min-width: 32px !important; max-width: 32px !important; }

    /* =====================================================================
     * Rumus Matematika Inline Langsung di Dalam Text Editor (TipTap)
     * ===================================================================== */
    .tiptap-inline-math {
        display: inline-block;
        vertical-align: middle;
        margin: 0 0.25rem;
        padding: 0.15rem 0.45rem;
        border-radius: 0.375rem;
        background-color: rgba(37, 99, 235, 0.08);
        border: 1px solid rgba(37, 99, 235, 0.3);
        cursor: pointer;
        user-select: none;
        transition: all 0.15s ease;
        line-height: normal;
    }
    .tiptap-inline-math:hover {
        background-color: rgba(37, 99, 235, 0.18);
        border-color: rgba(37, 99, 235, 0.55);
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.15);
    }
    .tiptap-inline-math.ProseMirror-selectednode {
        outline: 2px solid #2563eb;
        background-color: rgba(37, 99, 235, 0.22);
    }
    .dark .tiptap-inline-math {
        background-color: rgba(59, 130, 246, 0.15);
        border-color: rgba(59, 130, 246, 0.35);
    }
    .dark .tiptap-inline-math:hover {
        background-color: rgba(59, 130, 246, 0.25);
        border-color: rgba(59, 130, 246, 0.6);
    }

    /* =====================================================================
     * Helper Rumus Visual (math-preview.blade.php)
     * ===================================================================== */
    .mp-wrap { display: flex; flex-direction: column; gap: .5rem; font-size: .875rem; }
    .mp-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; }
    .mp-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem; border-radius: .5rem; cursor: pointer;
        font-size: .75rem; font-weight: 600; line-height: 1.2;
        border: 1px solid rgb(209 213 219); background: #fff; color: rgb(55 65 81);
        transition: background .15s, border-color .15s, color .15s;
    }
    .mp-btn:hover { background: rgb(243 244 246); }
    .mp-btn.is-active { background: rgb(37 99 235); border-color: rgb(37 99 235); color: #fff; }
    .mp-btn-primary { background: rgb(37 99 235); border-color: rgb(37 99 235); color: #fff; }
    .mp-btn-primary:hover { background: rgb(29 78 216); }
    .mp-btn-sm { padding: .2rem .5rem; font-weight: 500; font-family: ui-monospace, monospace; }
    .mp-hint { margin-left: auto; font-size: .7rem; color: rgb(107 114 128); }

    .mp-panel {
        border: 1px solid rgb(229 231 235); border-radius: .75rem;
        background: rgb(249 250 251); padding: .75rem; display: flex; flex-direction: column; gap: .75rem;
    }
    .mp-tabs { display: flex; flex-wrap: wrap; gap: .25rem; border-bottom: 1px solid rgb(229 231 235); padding-bottom: .5rem; }
    .mp-row { display: flex; flex-wrap: wrap; gap: .5rem; align-items: flex-end; }
    .mp-field { display: flex; flex-direction: column; gap: .2rem; min-width: 120px; flex: 1 1 140px; }
    .mp-field label { font-size: .7rem; font-weight: 600; color: rgb(75 85 99); }
    .mp-input {
        width: 100%; padding: .35rem .6rem; border-radius: .5rem; font-size: .85rem;
        border: 1px solid rgb(209 213 219); background: #fff; color: rgb(17 24 39);
    }
    .mp-input:focus { outline: 2px solid rgb(59 130 246); outline-offset: -1px; }
    .mp-mono { font-family: ui-monospace, monospace; }
    .mp-label { font-size: .7rem; font-weight: 600; color: rgb(75 85 99); margin-bottom: .25rem; display: block; }
    .mp-formula {
        min-height: 48px; display: flex; align-items: center; justify-content: center;
        background: #fff; border: 1px dashed rgb(209 213 219); border-radius: .5rem; padding: .5rem; font-size: 1.1rem;
    }
    .mp-actions { display: flex; justify-content: flex-end; gap: .5rem; }

    .mp-preview {
        border: 1px dashed rgb(191 219 254); border-radius: .75rem; padding: .6rem .8rem; background: #fff;
    }
    .mp-preview-title { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: rgb(37 99 235); margin-bottom: .35rem; }
    .mp-preview-body { min-height: 28px; line-height: 1.7; color: rgb(17 24 39); overflow-x: auto; }
    .mp-preview-body p { margin: 0 0 .4rem; }
    .mp-preview-body table { border-collapse: collapse; }
    .mp-preview-body td, .mp-preview-body th { border: 1px solid rgb(209 213 219); padding: .2rem .5rem; }
    .mp-preview-body img { max-width: 100%; height: auto; }
    .mp-muted { color: rgb(156 163 175); font-style: italic; font-size: .8rem; }

    .dark .mp-btn { background: rgb(31 41 55); border-color: rgb(55 65 81); color: rgb(229 231 235); }
    .dark .mp-btn:hover { background: rgb(55 65 81); }
    .dark .mp-btn.is-active, .dark .mp-btn-primary { background: rgb(37 99 235); border-color: rgb(37 99 235); color: #fff; }
    .dark .mp-panel { background: rgb(17 24 39); border-color: rgb(55 65 81); }
    .dark .mp-tabs { border-color: rgb(55 65 81); }
    .dark .mp-input, .dark .mp-formula, .dark .mp-preview { background: rgb(3 7 18); border-color: rgb(55 65 81); color: rgb(243 244 246); }
    .dark .mp-preview-body { color: rgb(243 244 246); }
    .dark .mp-field label, .dark .mp-label, .dark .mp-hint { color: rgb(156 163 175); }
</style>

{{-- Script KaTeX Lokal dengan defer agar tidak memblokir loading halaman Admin/Guru --}}
<script defer src="{{ asset('vendor/katex/katex.min.js') }}"></script>
<script defer src="{{ asset('vendor/katex/contrib/auto-render.min.js') }}"></script>

<script>
    window.KATEX_DELIMITERS = [
        {left: '$$', right: '$$', display: true},
        {left: '$', right: '$', display: false},
        {left: '\\(', right: '\\)', display: false},
        {left: '\\[', right: '\\]', display: true}
    ];

    // Elemen yang TIDAK boleh dirender KaTeX via auto-render biasa
    const KATEX_SKIP_SELECTOR = '.ProseMirror, .fi-fo-rich-editor, [contenteditable="true"], .mp-ignore, textarea, input';

    function triggerKatexRender(targetElement) {
        let el = targetElement || document.body;
        if (typeof renderMathInElement !== 'function' || !el) return;
        const txt = el.textContent || '';
        if (!txt.includes('$') && !txt.includes('\\(') && !txt.includes('\\[')) return;
        if (el !== document.body && el.closest && el.closest(KATEX_SKIP_SELECTOR)) return;

        try {
            renderMathInElement(el, {
                delimiters: window.KATEX_DELIMITERS,
                ignoredClasses: ['ProseMirror', 'fi-fo-rich-editor', 'mp-ignore'],
                throwOnError: false
            });
        } catch (e) {
            console.warn('KaTeX render warning:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        triggerKatexRender();
    });

    document.addEventListener('livewire:navigated', () => {
        setTimeout(triggerKatexRender, 200);
    });

    const mathObserver = new MutationObserver((mutations) => {
        for (let mutation of mutations) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        triggerKatexRender(node);
                    }
                });
            }
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        mathObserver.observe(document.body, { childList: true, subtree: true });
    });

    /**
     * Komponen Alpine untuk bantuan rumus + pratinjau soal.
     */
    window.mathHelper = function () {
        return {
            rawText: '',
            showBuilder: false,
            showPreview: false, // Default tertutup agar tidak membingungkan guru
            activeTab: 'pecahan',
            fracNum: 'a', fracDen: 'b',
            sqrtVal: 'x',
            powBase: 'x', powExp: '2',
            subBase: 'x', subVal: '1',
            customFormula: '',
            target: null,
            observer: null,
            symbols: ['\\pm', '\\times', '\\div', '\\le', '\\ge', '\\ne', '\\approx', '\\pi', '\\alpha', '\\beta', '\\theta', '\\Delta', '\\infty', '^\\circ', '\\int', '\\sum'],
            presets: [
                { label: 'Rumus ABC', value: 'x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}' },
                { label: 'Pythagoras', value: 'c = \\sqrt{a^2 + b^2}' },
                { label: 'Luas Lingkaran', value: 'L = \\pi r^2' },
                { label: 'H₂O', value: '\\text{H}_2\\text{O}' },
            ],

            init() {
                this.attach(0);
                this.$watch('activeTab', () => this.renderFormula());
            },

            destroy() {
                if (this.observer) this.observer.disconnect();
            },

            findTarget() {
                const scope = this.$root.closest('.fi-section') || this.$root.parentElement;
                if (!scope) return null;
                return scope.querySelector('.ProseMirror[contenteditable="true"], [contenteditable="true"], textarea');
            },

            attach(attempt) {
                const el = this.findTarget();
                if (!el) {
                    if (attempt < 20) setTimeout(() => this.attach(attempt + 1), 150);
                    return;
                }
                this.target = el;
                const update = () => this.syncFromTarget();
                el.addEventListener('input', update);
                el.addEventListener('blur', update);
                this.observer = new MutationObserver(update);
                this.observer.observe(el, { childList: true, subtree: true, characterData: true });
                update();
            },

            syncFromTarget() {
                const el = this.target;
                if (!el) return;
                this.rawText = el.isContentEditable ? (el.innerHTML || '') : (el.value || '');
                if (this.showPreview) this.renderPreview();
            },

            getFormula() {
                switch (this.activeTab) {
                    case 'pecahan': return `\\frac{${this.fracNum.trim() || 'a'}}{${this.fracDen.trim() || 'b'}}`;
                    case 'akar': return `\\sqrt{${this.sqrtVal.trim() || 'x'}}`;
                    case 'pangkat': return `${this.powBase.trim() || 'x'}^{${this.powExp.trim() || '2'}}`;
                    case 'subskrip': return `${this.subBase.trim() || 'x'}_{${this.subVal.trim() || '1'}}`;
                    default: return this.customFormula.trim();
                }
            },

            renderFormula() {
                this.$nextTick(() => {
                    const el = this.$refs.formula;
                    if (!el) return;
                    const f = this.getFormula();
                    if (!f) { el.innerHTML = '<span class="mp-muted">Rumus belum diisi</span>'; return; }
                    try {
                        el.innerHTML = window.katex
                            ? window.katex.renderToString(f, { displayMode: true, throwOnError: false })
                            : f;
                    } catch (e) {
                        el.textContent = e.message;
                    }
                });
            },

            addSymbol(sym) {
                this.activeTab = 'custom';
                this.customFormula = (this.customFormula + ' ' + sym + ' ').replace(/\s+/g, ' ');
                this.renderFormula();
            },

            setPreset(value) {
                this.activeTab = 'custom';
                this.customFormula = value;
                this.renderFormula();
            },

            toggleBuilder() {
                this.showBuilder = !this.showBuilder;
                if (this.showBuilder) this.renderFormula();
            },

            insertFormula() {
                const f = this.getFormula();
                if (!f) return;

                const scope = this.$root.closest('.fi-section') || this.$root.parentElement;
                const richEl = scope ? scope.querySelector('.fi-fo-rich-editor') : null;
                let editor = null;
                if (richEl && window.Alpine?.$data) {
                    try { editor = window.Alpine.$data(richEl)?.getEditor(); } catch(e) {}
                }
                if (!editor && richEl && richEl._x_dataStack?.length) {
                    try { editor = richEl._x_dataStack[0]?.getEditor(); } catch(e) {}
                }

                if (editor && editor.chain) {
                    const hasInlineMath = Boolean(editor.schema?.nodes?.inlineMath);
                    if (hasInlineMath) {
                        // Sisipkan node inlineMath langsung ter-render di teks editor!
                        editor.chain().focus().insertContent({
                            type: 'inlineMath',
                            attrs: { latex: f }
                        }).insertContent(' ').run();
                    } else {
                        editor.chain().focus().insertContent(` $${f}$ `).run();
                    }
                } else {
                    const text = ' $' + f + '$ ';
                    const el = this.target || this.findTarget();
                    if (el) {
                        if (el.isContentEditable) {
                            el.focus();
                            document.execCommand('insertText', false, text);
                        } else {
                            const start = el.selectionStart ?? el.value.length;
                            const end = el.selectionEnd ?? el.value.length;
                            el.value = el.value.substring(0, start) + text + el.value.substring(end);
                            el.selectionStart = el.selectionEnd = start + text.length;
                            el.focus();
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    }
                }
                this.syncFromTarget();
                this.showBuilder = false;
            },

            renderPreview() {
                const box = this.$refs.preview;
                if (!box) return;
                const text = (this.rawText || '').trim();
                if (!text || text === '<p></p>' || text === '<p><br></p>' || /^<p[^>]*>\s*(<br[^>]*>)?\s*<\/p>$/.test(text)) {
                    box.innerHTML = '<span class="mp-muted">Pratinjau soal (rumus, tabel, teks Arab) tampil di sini...</span>';
                    return;
                }
                box.innerHTML = text.indexOf('<') === -1 ? text.replace(/\n/g, '<br>') : text;
                box.querySelectorAll('[contenteditable]').forEach(n => n.removeAttribute('contenteditable'));
                box.querySelectorAll('.ProseMirror-trailingBreak, .ProseMirror-separator').forEach(n => n.remove());
                if (window.renderMathInElement) {
                    try {
                        window.renderMathInElement(box, { delimiters: window.KATEX_DELIMITERS, throwOnError: false });
                    } catch (e) {}
                }
            },
        };
    };
</script>
