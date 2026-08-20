<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @pushOnce('scripts')
        <script src="https://editor.unlayer.com/embed.js"></script>
    @endpushOnce

    @php
        $editorId  = 'ue_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $getId());
        $statePath = $getStatePath();
        $projectId = config('filament-email-templates.unlayer_project_id', '');
        $mergeTags = $getMergeTags();
    @endphp

    @push('scripts')
    <script>
    (function () {
        var COMPONENT = {{ Js::from($editorId) }};
        var STATE_PATH = {{ Js::from($statePath) }};
        var PROJECT_ID = {{ Js::from($projectId) }};
        var MERGE_TAGS = {{ Js::from($mergeTags) }};
        var CONTAINER_ID = 'unlayer-editor-' + COMPONENT;

        function makeUnlayerComponent() {
            return {
                unlayerReady: false,
                isSaving: false,
                initialLoadDone: false,

                init: function () {
                    var self = this;

                    function startBootstrap() {
                        if (!window.unlayer) { setTimeout(startBootstrap, 100); return; }
                        self.bootEditor();
                    }
                    startBootstrap();

                    // Observe dark mode changes to update Unlayer theme dynamically
                    const observer = new MutationObserver((mutations) => {
                        mutations.forEach((mutation) => {
                            if (mutation.attributeName === 'class') {
                                const isDark = document.documentElement.classList.contains('dark');
                                var instance = self.getEditorInstance();
                                if (instance) {
                                    instance.setAppearance({ theme: isDark ? 'modern_dark' : 'modern_light' });
                                    // instance.setBodyValues({ backgroundColor: isDark ? '#161616' : '#f9f9f9' });
                                } else if (window.unlayer) {
                                    unlayer.setTheme(isDark ? 'modern_dark' : 'modern_light');
                                }
                            }
                        });
                    });
                    observer.observe(document.documentElement, { attributes: true });

                    document.addEventListener('submit', function (e) {
                        const container = document.getElementById(CONTAINER_ID);
                        if (container && e.target.contains(container)) {
                            self.handleInterceptedSave(e);
                        }
                    }, true);

                    document.addEventListener('click', function (e) {
                        const btn = e.target.closest('button.fi-btn, button[type="submit"]');
                        if (!btn) return;

                        const text = btn.innerText.trim();
                        // Only intercept if it's a primary action button (Save/Create)
                        if (text.includes('Save') || text.includes('Create')) {
                            const container = document.getElementById(CONTAINER_ID);
                            const form = btn.closest('form');
                            
                            // If the button is inside a form, only intercept if it's OUR form. 
                            // If it's outside a form (header action), we intercept as long as our editor is present.
                            if (!form || (container && form.contains(container))) {
                                self.handleInterceptedSave(e);
                            }
                        }
                    }, true);
                },

                handleInterceptedSave: function (e) {
                    if (this.isSaving) return;
                    
                    var instance = this.getEditorInstance();
                    if (!instance || !this.unlayerReady) return;

                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    this.isSaving = true;
                    var self = this;

                    instance.exportHtml(function (data) {
                        // Pass both HTML and Design JSON back to server
                        self.$wire.call('syncUnlayerExport', data.html, data.design)
                            .finally(function () {
                                self.isSaving = false;
                            });
                    });
                },

                getEditorInstance: function () {
                    return (window.unlayer_editors && window.unlayer_editors[CONTAINER_ID])
                        ? window.unlayer_editors[CONTAINER_ID]
                        : null;
                },

                loadDesign: function (rawState) {
                    var instance = this.getEditorInstance();
                    if (!instance || !this.unlayerReady || !rawState) return;
                    try {
                        var parsed = (typeof rawState === 'string') ? JSON.parse(rawState) : rawState;
                        var design = parsed.design || null;
                        var html = parsed.html || '';

                        if (design) {
                            // PRIORITIZE JSON for perfect rebuilding
                            instance.loadDesign(design);
                        } else if (html) {
                            // FALLBACK to HTML if JSON is missing
                            instance.loadHtml(html);
                        }

                        var self = this;
                        setTimeout(function () {
                            self.initialLoadDone = true;
                        }, 1500);
                    } catch (err) {
                        console.error('[UnlayerEditor] Load failed:', err);
                        this.initialLoadDone = true;
                    }
                },

                bootEditor: function () {
                    if (window.unlayer_editors && window.unlayer_editors[CONTAINER_ID]) return;
                    window.unlayer_editors = window.unlayer_editors || {};
                    var container = document.getElementById(CONTAINER_ID);
                    if (!container) return;
                    container.innerHTML = '';
                    var self = this;
                    setTimeout(function () {
                        try {
                            var isDarkMode = document.documentElement.classList.contains('dark');
                            var editorConfig = {
                                id: CONTAINER_ID,
                                displayMode: 'email',
                                mergeTags: MERGE_TAGS,
                                appearance: {
                                    theme: isDarkMode ? 'modern_dark' : 'modern_light'
                                },
                                // tools: {
                                //     bodies: {
                                //         properties: {
                                //             backgroundColor: {
                                //                 editor: {
                                //                     defaultValue: isDarkMode ? '#161616' : '#f9f9f9',
                                //                 },
                                //             },
                                //         },
                                //     },
                                // },
                            };

                            // Only append projectId if it's explicitly set and greater than 0
                            // Note: if dark theme breaks with your real projectId, it means your Unlayer plan does not support custom appearance.
                            if (PROJECT_ID && PROJECT_ID != 'null' && PROJECT_ID != '0') {
                                editorConfig.projectId = PROJECT_ID;
                            }

                            window.unlayer_editors[CONTAINER_ID] = unlayer.createEditor(editorConfig);
                            var instance = self.getEditorInstance();

                            instance.addEventListener('editor:ready', function () {
                                self.unlayerReady = true;
                                setTimeout(function () {
                                    var raw = self.$wire.get(STATE_PATH);
                                    self.loadDesign(raw);
                                }, 300);
                            });

                            instance.addEventListener('design:updated', function () {
                                if (!self.initialLoadDone) return;
                                instance.exportHtml(function (data) {
                                    self.$wire.set(STATE_PATH, JSON.stringify({
                                        design: data.design,
                                        html: data.html
                                    }), false);
                                });
                            });
                        } catch (e) {
                            console.error('[UnlayerEditor] Boot error:', e);
                        }
                    }, 100);
                }
            };
        }

        function register() {
            Alpine.data(COMPONENT, makeUnlayerComponent);
        }

        if (window.Alpine && window.Alpine.data) {
            register();
        } else {
            document.addEventListener('alpine:init', register);
        }
    })();
    </script>
    @endpush

    <div
        x-data="{{ $editorId }}"
        wire:ignore
        class="border border-gray-300 rounded-lg overflow-hidden shadow-sm dark:border-gray-700 bg-white dark:bg-gray-900"
    >
        <div id="unlayer-editor-{{ $editorId }}" style="height: 750px; width: 100%;"></div>
    </div>
</x-dynamic-component>
