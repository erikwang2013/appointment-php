const api = require('../../utils/api');

// 契约对齐：FavoriteController 返回全量数组（无服务端分页），
// 每项 { id, target_type, target_id, created_at, target?: {...} }
// service 的 target: { id, name, cover_image, price, sales_volume }
// technician 的 target: { id, real_name, avatar, rating, order_count }
function decorate(item) {
  const t = item.target;
  if (!t) return { ...item, title: '已下架内容', subtitle: '', image: '' };
  if (item.target_type === 'service') {
    return {
      ...item,
      title: t.name,
      subtitle: '¥' + (t.price ?? '--') + (t.sales_volume ? ' · 已售' + t.sales_volume : ''),
      image: t.cover_image || ''
    };
  }
  return {
    ...item,
    title: t.real_name,
    subtitle: (t.rating ? '评分 ' + t.rating : '技师'),
    image: t.avatar || ''
  };
}

Page({
  data: { list: [], loading: false },

  onShow() {
    this.load();
  },

  onPullDownRefresh() {
    this.load().finally(() => wx.stopPullDownRefresh());
  },

  async load() {
    if (this._loading) return;
    this._loading = true;
    this.setData({ loading: true });
    try {
      const res = await api.authGet('/user/favorites');
      this.setData({ list: (res.data || []).map(decorate) });
    } catch (e) {
      wx.showToast({ title: '加载失败', icon: 'none' });
    } finally {
      this._loading = false;
      this.setData({ loading: false });
    }
  },

  // 取消收藏：DELETE /user/favorites/{id}，成功后本地移除
  onUnfavorite(e) {
    const id = e.currentTarget.dataset.id;
    if (!id) return;
    wx.showModal({
      title: '取消收藏',
      content: '确定要取消收藏吗？',
      success: (r) => {
        if (!r.confirm) return;
        api.del('/user/favorites/' + id).then(() => {
          this.setData({ list: this.data.list.filter((i) => i.id !== id) });
          wx.showToast({ title: '已取消收藏', icon: 'success' });
        }).catch(() => {
          wx.showToast({ title: '操作失败', icon: 'none' });
        });
      }
    });
  }
});
