/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  const h = String(d.getHours()).padStart(2, '0');
  const min = String(d.getMinutes()).padStart(2, '0');
  const s = String(d.getSeconds()).padStart(2, '0');
  return `${y}-${m}-${day} ${h}:${min}:${s}`;
}

function formatNumber(n) {
  if (n >= 10000) {
    return (n / 10000).toFixed(1) + 'w';
  }
  if (n >= 1000) {
    return (n / 1000).toFixed(1) + 'k';
  }
  return String(n);
}

function showConfirm(title, content) {
  return new Promise((resolve) => {
    wx.showModal({
      title: title || '提示',
      content: content || '确定要执行此操作吗？',
      success(res) {
        resolve(res.confirm);
      }
    });
  });
}

module.exports = {
  formatDate,
  formatNumber,
  showConfirm
};
