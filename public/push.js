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
    const worker = await navigator.serviceWorker.register('/sw.js?v=7', { scope: '/' });
    if (worker.active) return worker;

    return Promise.race([
      navigator.serviceWorker.ready,
      new Promise((_, reject) => window.setTimeout(() => reject(new Error('The notification service is taking too long to start. Please refresh the app and try again.')), 10000)),
    ]);
  };

  const requiresInstalledApp = () => {
    const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const standalone = window.matchMedia?.('(display-mode: standalone)').matches || window.navigator.standalone === true;
    return isIos && !standalone;
  };

  window.AlasPush = {
    configure: (key) => { publicKey = key; },
    subscribe: async () => {
      if (!publicKey) throw new Error('Push notifications are not configured yet.');
      if (requiresInstalledApp()) throw new Error('On iPhone or iPad, add ALAS to your Home Screen first, then open the installed app and enable notifications there.');
      const permission = await Notification.requestPermission();
      if (permission !== 'granted') throw new Error('Notification permission was not granted.');
      const worker = await registration();
      const subscription = await worker.pushManager.getSubscription()
        || await worker.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: base64UrlToUint8Array(publicKey) });
      await request('/push-subscriptions', { method: 'POST', body: JSON.stringify(subscription.toJSON()) });
    },
    status: async () => {
      if (Notification.permission !== 'granted') return false;
      try {
        const worker = await registration();
        return Boolean(await worker.pushManager.getSubscription());
      } catch (_) {
        return false;
      }
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
