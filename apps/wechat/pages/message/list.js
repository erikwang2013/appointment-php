const api = require('../../utils/api');
Page({
  data: { messages: [], page: 1, perPage: 10, hasMore: true },
  onShow() { this.loadMessages(true); },
  async loadMessages(reset) {
    if (reset) this.setData({ page: 1, messages: [], hasMore: true });
    if (this._loading || !this.data.hasMore) return;
    const { page, perPage } = this.data;
    // 契约对齐：服务端 NotificationController::index 读 page / per_page，返回 meta.has_more
    this._loading = true;
    try {
      const res = await api.authGet('/notification', { page, per_page: perPage });
      const list = res.data || [];
      const hasMore = res.meta ? !!res.meta.has_more : list.length >= perPage;
      this.setData({ messages: page === 1 ? list : [...this.data.messages, ...list], hasMore, page: hasMore ? page + 1 : page });
    } catch(e) {}
    finally { this._loading = false; }
  },
  onReachBottom() { if (this.data.hasMore) this.loadMessages(); },
  onRead(e) { api.put('/notification/read/' + e.currentTarget.dataset.id).then(() => this.loadMessages(true)); }
});
