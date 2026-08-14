const api = require('../../../utils/api');

Page({
  data: {
    loading: true,
    service: null,
    technicians: [],
    technicianIndex: -1,
    date: '',
    time: '10:00',
    remark: ''
  },
  onLoad(options) {
    this.serviceId = options.id || '';
    this.setData({ date: this.defaultDate() });
    this.loadService();
    this.loadTechnicians();
  },
  defaultDate() {
    const d = new Date(Date.now() + 3600 * 1000);
    const pad = n => (n < 10 ? '0' + n : '' + n);
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  },
  async loadService() {
    try {
      const res = await api.get('/service/detail/' + this.serviceId);
      this.setData({ service: res.data });
    } catch (e) {
      wx.showToast({ title: '服务加载失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },
  async loadTechnicians() {
    try {
      const res = await api.get('/technician/list');
      const list = (res.data || []).map(t => ({
        id: t.id,
        name: t.name || t.nickname || '技师'
      }));
      this.setData({ technicians: list });
    } catch (e) { /* 无技师时隐藏选择 */ }
  },
  onTechnicianChange(e) {
    this.setData({ technicianIndex: Number(e.detail.value) });
  },
  onDateChange(e) {
    this.setData({ date: e.detail.value });
  },
  onTimeChange(e) {
    this.setData({ time: e.detail.value });
  },
  onRemarkInput(e) {
    this.setData({ remark: e.detail.value });
  },
  goConfirm() {
    const { service, technicians, technicianIndex, date, time, remark } = this.data;
    if (!service) return;
    if (technicians.length > 0 && technicianIndex < 0) {
      wx.showToast({ title: '请选择技师', icon: 'none' });
      return;
    }
    if (!date || !time) {
      wx.showToast({ title: '请选择服务时间', icon: 'none' });
      return;
    }
    const tech = technicianIndex >= 0 ? technicians[technicianIndex] : null;
    const q = {
      id: service.id,
      name: service.name,
      price: service.price,
      image: service.image || '',
      service_time: date + ' ' + time,
      remark: remark || ''
    };
    if (tech) {
      q.technician_id = tech.id;
      q.technician_name = tech.name;
    }
    const arr = Object.keys(q).map(k => k + '=' + encodeURIComponent(q[k] == null ? '' : q[k]));
    wx.navigateTo({ url: '/pages/order/confirm?' + arr.join('&') });
  }
});
