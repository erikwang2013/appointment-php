// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

// 订阅消息模板 ID 集中管理。
// 键名必须与服务端 erik_system_config.wechat_app.template_ids 的键保持一致：
// order_confirm / service_reminder / refund_notify / technician_assigned。
// 实际 ID 需在微信公众平台申请后填入，并同步到后台配置，两侧不一致将无法送达。
const TEMPLATE_IDS = {
  order_confirm: '',        // 订单确认通知
  service_reminder: '',     // 服务开始前提醒（预约前 2h~1h）
  refund_notify: '',        // 退款完成通知
  technician_assigned: ''   // 技师分配通知
};

/**
 * 请求订阅授权。必须在用户手势回调中调用；未配置模板 ID 或调用失败时静默跳过。
 * @param {string[]} keys TEMPLATE_IDS 的键数组，如 ['order_confirm', 'service_reminder']
 */
function requestSubscribe(keys) {
  if (!wx.requestSubscribeMessage) return;
  const tmplIds = (keys || []).map(k => TEMPLATE_IDS[k]).filter(Boolean);
  if (!tmplIds.length) return;
  wx.requestSubscribeMessage({
    tmplIds: tmplIds,
    success: function () {},
    fail: function () {} // 用户拒绝/取消/失败均静默处理，不打扰用户
  });
}

module.exports = {
  TEMPLATE_IDS: TEMPLATE_IDS,
  requestSubscribe: requestSubscribe
};
