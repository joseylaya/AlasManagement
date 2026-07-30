(() => {
  const DB = 'alas-offline'; const STORE = 'sync_queue'; let userId = null;
  const open = () => new Promise((resolve, reject) => { const req = indexedDB.open(DB, 1); req.onupgradeneeded = () => req.result.createObjectStore(STORE, { keyPath: 'local_uuid' }); req.onsuccess = () => resolve(req.result); req.onerror = () => reject(req.error); });
  const records = async () => { const db = await open(); return new Promise((resolve, reject) => { const req = db.transaction(STORE).objectStore(STORE).getAll(); req.onsuccess = () => resolve(req.result); req.onerror = () => reject(req.error); }); };
  const put = async (record) => { const db = await open(); return new Promise((resolve, reject) => { const req = db.transaction(STORE, 'readwrite').objectStore(STORE).put(record); req.onsuccess = resolve; req.onerror = () => reject(req.error); }); };
  const announce = async () => window.dispatchEvent(new CustomEvent('alas-sync-status', { detail: { online: navigator.onLine, pending: (await records()).filter(r => r.local_status !== 'synced').length } }));
  const queue = async (record_type, payload) => {
    if (!userId) throw new Error('Offline storage requires a signed-in user.');
    const now = new Date().toISOString();
    await put({ local_uuid: crypto.randomUUID(), record_type, action_type: 'create', payload, local_status: 'pending_sync', created_at_local: now, updated_at_local: now, created_by: userId, sync_attempts: 0, last_sync_attempt_at: null, sync_error: null, server_id: null });
    await announce();
    window.dispatchEvent(new CustomEvent('alas-offline-saved', { detail: { record_type } }));
    if (navigator.onLine) sync();
  };
  const sync = async () => {
    if (!navigator.onLine || !userId) return announce();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    for (const record of (await records()).filter(r => r.created_by === userId && ['pending_sync', 'sync_failed'].includes(r.local_status)).sort((a,b) => a.created_at_local.localeCompare(b.created_at_local))) {
      record.local_status = 'syncing'; record.sync_attempts++; record.last_sync_attempt_at = new Date().toISOString(); await put(record);
      const path = record.record_type === 'owner_withdrawal' ? '/sync/owner-withdrawals' : '/sync/orders';
      try {
        const response = await fetch(path, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ client_uuid: record.local_uuid, payload: record.payload }) });
        const body = await response.json();
        if (!response.ok) { record.local_status = body.status === 'conflict' ? 'conflict' : 'sync_failed'; record.sync_error = body.message || 'Unable to synchronize this record.'; }
        else { record.local_status = 'synced'; record.server_id = body.server_id; record.sync_error = null; }
      } catch (_) { record.local_status = 'sync_failed'; record.sync_error = 'Connection interrupted. Your record remains stored on this device.'; }
      await put(record);
    }
    await announce();
  };
  const queueWithdrawal = async () => {
    const amount = window.prompt('Owner withdrawal amount (₱)'); if (!amount || Number(amount) <= 0) return;
    const reason = window.prompt('Reason for withdrawal'); if (!reason) return;
    const payment_source = window.prompt('Payment source', 'cash') || 'cash';
    const remarks = window.prompt('Remarks (optional)') || '';
    await queue('owner_withdrawal', { amount, reason, payment_source, remarks, drawal_date: new Date().toISOString().slice(0, 10) });
  };
  window.AlasOffline = { setUser: (id) => { userId = id; announce(); sync(); }, queue, queueWithdrawal, sync, records };
  window.addEventListener('alas-offline-saved', event => window.alert(`${event.detail.record_type === 'sale' ? 'Sale' : 'Owner withdrawal'} saved on this device. It will synchronize when you are online.`));
  window.addEventListener('online', sync); window.addEventListener('offline', announce); window.addEventListener('load', () => { if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js'); });
})();
