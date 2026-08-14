const api = require('../../utils/api');

// 类型/来源展示文案（与 erik_user_points 的 type/source 取值对齐）
const TYPE_LABELS = { earn: '获得', use: '使用', expire: '过期', income: '获得' };
const SOURCE_LABELS = {
  order: '订单消费',
  referral: '推荐奖励',
  gift_card: '礼品卡',
  admin: '后台调整',
  check_in: '每日签到',
};

Page({
  data: {
    balance: 0,
    records: [],
    page: 1,
    perPage: 10,
    hasMore: true,
  },
  onShow() { this.loadPoints(true); },
  async loadPoints(reset) {
    if (reset) this.setData({ page: 1, records: [], hasMore: true });
    if (this._loading || !this.data.hasMore) return;
    const { page, perPage } = this.data;
    this._loading = true;
    try {
      const res = await api.authGet('/marketing/points', { page, per_page: perPage });
      const records = (res.records || []).map((r) => ({
        ...r,
        type_label: TYPE_LABELS[r.type] || r.type,
        source_label: SOURCE_LABELS[r.source] || r.source,
        points_text: (r.points > 0 ? '+' : '') + r.points,
      }));
      const hasMore = res.meta ? !!res.meta.has_more : records.length >= perPage;
      this.setData({
        balance: res.balance || 0,
        records: page === 1 ? records : [...this.data.records, ...records],
        hasMore,
        page: hasMore ? page + 1 : page,
      });
    } catch (e) {}
    finally { this._loading = false; }
  },
  onReachBottom() { if (this.data.hasMore) this.loadPoints(); },
  onPullDownRefresh() {
    this.loadPoints(true).finally(() => wx.stopPullDownRefresh());
  },
});
