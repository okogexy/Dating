let csrf = document.querySelector('meta[name="csrf-token"]').content;
export const demoAvailable = document.querySelector('meta[name="demo-enabled"]')?.content === '1';
export async function api(path, method = 'GET', body) {
    const response = await fetch('/api' + path, { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(body instanceof FormData ? {} : { 'Content-Type': 'application/json' }) }, ...(body ? { body: body instanceof FormData ? body : JSON.stringify(body) } : {}) });
    const data = await response.json();
    if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Не удалось выполнить запрос. Попробуйте ещё раз.');
    if (data.csrf) csrf = data.csrf;
    return data;
}
