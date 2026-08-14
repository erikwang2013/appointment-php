const api = require('../../utils/api');

// 契约对齐：NotificationController 的 type 实际值（order=核销/退款/提醒，community=点赞/评论，其余为推送类）
const TYPE_META = {
  order: { icon: '📦', label: '订单' },
  community: { icon: '💬', label: '社区' },
  order_update: { icon: '📋', label: '订单' },
  technician_online: { icon: '🛠️', label: '技师' }
};
const DEFAULT_META = { icon: '🔔', label: '系统' };

function pad(n) {
  return n < 10 ? '0' + n : '' + n;
}

// 服务端 created_at 为 "Y-m-d H:i:s"：今天只显示时分，今年显示月日，更早显示完整日期
function formatTime(t) {
  if (!t) return '';
  const d = new Date(String(t).replace(/-/g, '/'));
  if (isNaN(d.getTime())) return t;
  const now = new Date();
  const hm = pad(d.getHours()) + ':' + pad(d.getMinutes());
  if (d.toDateString() === now.toDateString()) return hm;
  const md = pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  if (d.getFullYear() === now.getFullYear()) return md + ' ' + hm;
  return d.getFullYear() + '-' + md;
}

function decorate(item) {
  const meta = TYPE_META[item.type] || DEFAULT_META;
  return {
    ...item,
    icon: meta.icon,
    type_label: meta.label,
    time_text: formatTime(item.created_at)
  };
}

Page({
  data: { list: [], page: 1, perPage: 15, hasMore: true, loading: false },

  onShow() {
    this.load(true);
  },

  onPullDownRefresh() {
    this.load(true).finally(() => wx.stopPullDownRefresh());
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) this.load(false);
  },

  async load(reset) {
    if (this._loading) return;
    if (reset) this.setData({ page: 1, list: [], hasMore: true });
    if (!this.data.hasMore) return;
    this._loading = true;
    this.setData({ loading: true });
    try {
      const res = await api.authGet('/notification', { page: this.data.page, per_page: this.data.perPage });
      const list = (res.data || []).map(decorate);
      const hasMore = res.meta ? !!res.meta.has_more : list.length >= this.data.perPage;
      this.setData({
        list: this.data.page === 1 ? list : this.data.list.concat(list),
        hasMore,
        page: hasMore ? this.data.page + 1 : this.data.page
      });
    } catch (e) {
      wx.showToast({ title: '加载失败', icon: 'none' });
    } finally {
      this._loading = false;
      this.setData({ loading: false });
    }
  },

  // 点条目：未读则调 read/{id} 接口并本地置已读，避免整页刷新
  onRead(e) {
    const index = e.currentTarget.dataset.index;
    const item = this.data.list[index];
    if (!item || item.is_read) return;
    api.put('/notification/read/' + item.id).then(() => {
      const key = 'list[' + index + '].is_read';
      this.setData({ [key]: 1 });
    }).catch(() => {
      wx.showToast({ title: '操作失败', icon: 'none' });
    });
  },

  // 全部已读
  onReadAll() {
    api.put('/notification/read-all').then((res) => {
      const updated = res.data && res.data.updated_count;
      if (updated > 0) {
        this.setData({ list: this.data.list.map((i) => ({ ...i, is_read: 1 })) });
      }
      wx.showToast({ title: updated > 0 ? '已全部标记为已读' : '没有未读消息', icon: 'none' });
    }).catch(() => {
      wx.showToast({ title: '操作失败', icon: 'none' });
    });
  }
});
