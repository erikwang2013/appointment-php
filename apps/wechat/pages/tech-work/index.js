const api = require('../../utils/api');
const STATUS_TEXT = { confirmed: '待服务', serving: '服务中', completed: '已完成' };

Page({
  data: {
    activeTab: 'scan', // scan | tasks | records
    earnings: { today_income: '0.00', pending: '0.00', balance: '0.00' },
    todayOrders: 0,
    tasks: [],
    records: [],
    recordPage: 1,
    recordHasMore: false,
    loading: false
  },
  onShow() {
    this.loadSummary();
    this.loadTabData();
  },
  // ── Tab 切换 ──
  onTabTap(e) {
    const tab = e.currentTarget.dataset.tab;
    if (tab === this.data.activeTab) return;
    this.setData({ activeTab: tab });
    this.loadTabData();
  },
  loadTabData() {
    if (this.data.activeTab === 'tasks') this.loadTasks();
    else if (this.data.activeTab === 'records') this.loadRecords(1);
  },
  // ── 汇总 ──
  async loadSummary() {
    try {
      const [earnRes, orderRes] = await Promise.all([api.authGet('/technician/earnings'), api.authGet('/technician/orders?page=1')]);
      const summary = (earnRes.data && earnRes.data.summary) || {};
      this.setData({
        earnings: {
          today_income: summary.today_income || '0.00',
          pending: summary.pending_settlement || '0.00',
          balance: summary.balance || '0.00'
        },
        todayOrders: (orderRes.data || []).length
      });
    } catch (e) { /* not technician */ }
  },
  // ── 今日任务 ──
  async loadTasks() {
    try {
      const res = await api.authGet('/technician/work/today');
      const tasks = (res.data || []).map(t => this.decorate(t));
      this.setData({ tasks });
    } catch (e) {
      wx.showToast({ title: '加载任务失败', icon: 'none' });
    }
  },
  // ── 完成记录（分页）──
  async loadRecords(page) {
    if (this.data.loading) return;
    this.setData({ loading: true });
    try {
      const res = await api.authGet('/technician/work/records?page=' + page);
      const list = (res.data || []).map(r => this.decorate(r));
      const meta = res.meta || {};
      this.setData({
        records: page === 1 ? list : this.data.records.concat(list),
        recordPage: page,
        recordHasMore: !!meta.has_more
      });
    } catch (e) {
      wx.showToast({ title: '加载记录失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },
  onLoadMore() {
    if (this.data.recordHasMore && !this.data.loading) this.loadRecords(this.data.recordPage + 1);
  },
  decorate(item) {
    return Object.assign({}, item, {
      status_text: STATUS_TEXT[item.status] || item.status,
      service_time_text: this.fmtTime(item.service_time),
      start_time_text: this.fmtTime(item.service_start_at),
      end_time_text: this.fmtTime(item.service_end_at)
    });
  },
  fmtTime(v) {
    if (!v) return '';
    const t = new Date(String(v).replace(/-/g, '/'));
    if (isNaN(t.getTime())) return String(v);
    const p = n => (n < 10 ? '0' + n : '' + n);
    return t.getFullYear() + '-' + p(t.getMonth() + 1) + '-' + p(t.getDate()) + ' ' + p(t.getHours()) + ':' + p(t.getMinutes());
  },
  // ── 开始/完成服务 ──
  onStartTap(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '开始服务',
      content: '确认开始服务该订单吗？',
      success: (r) => { if (r.confirm) this.doAction('/technician/work/' + id + '/start', '已开始服务'); }
    });
  },
  onCompleteTap(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '完成服务',
      content: '确认完成该订单的服务吗？',
      success: (r) => { if (r.confirm) this.doAction('/technician/work/' + id + '/complete', '服务已完成'); }
    });
  },
  doAction(url, successMsg) {
    wx.showLoading({ title: '处理中...' });
    api.authPost(url)
      .then(() => {
        wx.hideLoading();
        wx.showToast({ title: successMsg, icon: 'success' });
        this.loadTasks();
      })
      .catch((err) => {
        wx.hideLoading();
        const msg = (err && (err.message || err.msg)) || '操作失败';
        wx.showToast({ title: msg, icon: 'none' });
      });
  },
  // ── 扫码核销（保留第 5 轮能力）：扫描用户出示的订单核销码 → 调服务端核销 ──
  scanToVerify() {
    wx.scanCode({
      onlyFromCamera: true,
      scanType: ['qrCode', 'barCode'],
      success: (res) => {
        const code = (res.result || '').trim();
        if (!code) {
          wx.showToast({ title: '未识别到核销码', icon: 'none' });
          return;
        }
        wx.showLoading({ title: '核销中...' });
        api.authPost('/order/verify-by-code', { code })
          .then((resp) => {
            wx.hideLoading();
            const data = resp.data || {};
            const orderNo = data.order_no || '';
            if (data.already_verified) {
              wx.showModal({
                title: '已核销',
                content: '订单 ' + orderNo + ' 此前已核销，请勿重复核销',
                showCancel: false
              });
            } else {
              wx.showModal({
                title: '核销成功',
                content: '订单 ' + orderNo + ' 已开始服务',
                showCancel: false
              });
            }
            this.loadData();
          })
          .catch((err) => {
            wx.hideLoading();
            const msg = (err && (err.message || err.msg)) || '核销失败';
            wx.showModal({ title: '核销失败', content: msg, showCancel: false });
          });
      },
      fail: () => { /* 用户取消扫码 */ }
    });
  },
  // 兼容旧调用（核销后刷新）
  loadData() {
    this.loadSummary();
    this.loadTabData();
  }
});
