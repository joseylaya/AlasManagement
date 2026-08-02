(() => {
  let publicKey = null;
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
  const base64UrlToUint8Array = (value) => {
    const padding = '='.repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), char => char.charCodeAt(0));
  };
  const request = async (url, options) => {
    const response = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() }, ...options });
    if (!response.ok) throw new Error((await response.json().catch(() => ({}))).message || 'Unable to update push notifications.');
    return response.json();
  };
  const registration = async () => {
    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) throw new Error('This browser does not support push notifications.');
    return navigator.serviceWorker.ready;
  };
  window.AlasPush = {
    configure: (key) => { publicKey = key; },
    subscribe: async () => {
      if (!publicKey) throw new Error('Push notifications are not configured yet.');
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Notification permission was not granted.');
      const worker = await registration();
      const subscription = await worker.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: base64UrlToUint8Array(publicKey) });
      await request('/push-subscriptions', { method: 'POST', body: JSON.stringify(subscription.toJSON()) });
    },
    unsubscribe: async () => {
      const worker = await registration();
      const subscription = await worker.pushManager.getSubscription();
      if (!subscription) return;
      await request('/push-subscriptions', { method: 'DELETE', body: JSON.stringify({ endpoint: subscription.endpoint }) });
      await subscription.unsubscribe();
    },
  };
})();
