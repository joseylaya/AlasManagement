(() => {
  const endpoint = '/notifications/feed';

  const text = (value) => document.createTextNode(value || '');

  const item = (notification, compact) => {
    const element = document.createElement(notification.is_announcement ? 'button' : 'a');
    element.type = notification.is_announcement ? 'button' : undefined;
    if (!notification.is_announcement) element.href = notification.open_url;
    element.className = compact
      ? `mb-1 block w-full rounded-xl border-l-4 p-3 text-left transition-colors ${notification.is_read ? 'border-slate-200 bg-white' : 'border-blue-500 bg-blue-50/60'}`
      : `mb-2 block w-full rounded-2xl border-l-4 p-4 text-left transition-colors ${notification.is_read ? 'border-slate-200 bg-white' : 'border-blue-500 bg-[#F5F7FA]'}`;

    if (notification.is_announcement) {
      element.classList.remove('border-blue-500');
      element.classList.add('border-amber-400');
      element.addEventListener('click', () => window.dispatchEvent(new CustomEvent('alas:open-notification', { detail: notification })));
    }

    const title = document.createElement('p');
    title.className = compact ? 'text-[12px] font-bold text-[#222222]' : 'text-[13px] font-bold text-[#222222]';
    title.append(text(notification.title));
    const message = document.createElement('p');
    message.className = compact ? 'mt-1 line-clamp-2 text-[11px] leading-relaxed text-[#666666]' : 'mt-1 text-[12px] leading-relaxed text-[#666666] line-clamp-2';
    message.append(text(notification.message));
    const time = document.createElement('p');
    time.className = compact ? 'mt-1.5 text-[10px] text-[#999999]' : 'mt-2 text-[11px] text-[#999999]';
    time.append(text(notification.created_at));
    element.append(title, message, time);
    return element;
  };

  const render = (payload) => {
    const count = Number(payload.unread_count || 0);
    document.querySelectorAll('[data-notification-count]').forEach((badge) => {
      badge.textContent = Math.min(count, 99);
      badge.classList.toggle('hidden', count === 0);
    });
    document.querySelectorAll('[data-notification-summary]').forEach((summary) => {
      summary.textContent = count ? `${count} unread` : 'You’re all caught up';
    });
    document.querySelectorAll('[data-notification-list]').forEach((list) => {
      const compact = list.dataset.notificationList === 'desktop';
      const controls = list.querySelector(':scope > .mb-3');
      list.replaceChildren(...(controls ? [controls] : []));
      if (!payload.notifications.length) {
        const empty = document.createElement('p');
        empty.className = 'px-4 py-10 text-center text-[12px] text-[#777777]';
        empty.append(text('No notifications yet.'));
        list.append(empty);
        return;
      }
      payload.notifications.forEach((notification) => list.append(item(notification, compact)));
    });
  };

  const refresh = async () => {
    if (document.hidden) return;
    try {
      const response = await fetch(endpoint, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (response.ok) render(await response.json());
    } catch (_) {
      // The existing rendered notification state remains available offline.
    }
  };

  window.AlasNotificationFeed = { refresh };
  window.setInterval(refresh, 5000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
})();
