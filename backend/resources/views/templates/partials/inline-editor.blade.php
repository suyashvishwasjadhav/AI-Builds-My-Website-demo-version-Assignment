@if ($isPreview)
    <style>
        [data-inline-field] {
            outline: 1px dashed transparent;
            border-radius: 4px;
            transition: outline-color 0.15s ease, background-color 0.15s ease;
            cursor: text;
        }
        [data-inline-field]:hover {
            outline-color: rgba(99, 102, 241, 0.55);
            background-color: rgba(99, 102, 241, 0.05);
        }
        [data-inline-field]:focus {
            outline: 2px solid #4f46e5;
            background-color: rgba(99, 102, 241, 0.08);
        }
    </style>

    <div
        id="inline-save-toast"
        role="status"
        aria-live="polite"
        class="fixed bottom-5 left-5 z-50 hidden rounded-lg bg-slate-900 px-3.5 py-2 text-xs font-medium text-white shadow-lg"
    ></div>

    <script>
        (function () {
            const csrf = @js(csrf_token());
            const toast = document.getElementById('inline-save-toast');
            let toastTimer = null;

            function showToast(message, isError) {
                if (!toast) return;
                toast.textContent = message;
                toast.className = 'fixed bottom-5 left-5 z-50 rounded-lg px-3.5 py-2 text-xs font-medium text-white shadow-lg ' + (isError ? 'bg-rose-600' : 'bg-emerald-600');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toast.classList.add('hidden');
                }, 2200);
            }

            document.querySelectorAll('[data-inline-field]').forEach((el) => {
                el.setAttribute('contenteditable', 'true');
                let initialValue = el.innerText.trim();

                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        el.innerText = initialValue;
                        el.blur();
                    } else if (e.key === 'Enter' && el.dataset.inlineField !== 'body') {
                        e.preventDefault();
                        el.blur();
                    }
                });

                el.addEventListener('blur', () => {
                    const nextValue = el.innerText.trim();
                    if (nextValue === '' || nextValue === initialValue) {
                        if (nextValue === '') el.innerText = initialValue;
                        return;
                    }

                    const url = el.dataset.inlineUrl;
                    const field = el.dataset.inlineField;

                    fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ field, value: nextValue }),
                    })
                        .then((res) => {
                            if (!res.ok) throw new Error('Save failed');
                            return res.json();
                        })
                        .then(() => {
                            initialValue = nextValue;
                            showToast('Saved changes (version recorded)', false);
                        })
                        .catch(() => {
                            el.innerText = initialValue;
                            showToast('Could not save change', true);
                        });
                });
            });
        })();
    </script>
@endif
