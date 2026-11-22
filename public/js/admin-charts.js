/* LabBI Admin charts — dependency-free SVG renderer (RTL-aware).
   Usage:
     LabBICharts.area(el, { labels: [...], series: [{name, data: [...], color}] , height })
     LabBICharts.donut(el, { data: [{label, value, color}], centerLabel, centerValue })
     LabBICharts.bars(el, { data: [{label, value, color}] , horizontal })
     LabBICharts.sparkline(el, { data: [...], color })
   All numbers must be finite; empty data renders an empty-state message.
*/
(function () {
    'use strict';

    var NS = 'http://www.w3.org/2000/svg';
    var PALETTE = ['#0D9488', '#2563EB', '#7C3AED', '#0EA5E9', '#D97706', '#DC2626', '#16A34A', '#64748B'];

    function el(tag, attrs) {
        var n = document.createElementNS(NS, tag);
        for (var k in attrs) n.setAttribute(k, attrs[k]);
        return n;
    }
    function txt(x, y, s, attrs) {
        var t = el('text', Object.assign({ x: x, y: y, 'font-size': '10', fill: '#64748B', 'text-anchor': 'middle' }, attrs || {}));
        t.textContent = s;
        return t;
    }
    function emptyState(container, msg) {
        container.innerHTML = '';
        var d = document.createElement('div');
        d.className = 'empty';
        d.style.padding = '2rem 1rem';
        d.innerHTML = '<p style="margin:0">' + (msg || 'لا تتوفر بيانات للعرض') + '</p>';
        container.appendChild(d);
    }
    function clean(container) { container.innerHTML = ''; }

    /* ---------- Line / area chart ---------- */
    function area(container, opts) {
        clean(container);
        var labels = opts.labels || [];
        var series = (opts.series || []).filter(function (s) { return s.data && s.data.length; });
        if (!series.length || !labels.length) return emptyState(container);
        var W = container.clientWidth || 600, H = opts.height || 300;
        var padL = 44, padR = 14, padT = 14, padB = 30;
        var iw = W - padL - padR, ih = H - padT - padB;
        var all = [];
        series.forEach(function (s) { s.data.forEach(function (v) { if (isFinite(v)) all.push(v); }); });
        if (!all.length) return emptyState(container);
        var max = Math.max.apply(null, all), min = Math.min(0, Math.min.apply(null, all));
        if (max === min) max = min + 1;
        var niceMax = Math.ceil(max * 1.15);
        function X(i) { return padL + (iw * i) / Math.max(1, labels.length - 1); }
        function Y(v) { return padT + ih - ((v - min) / (niceMax - min)) * ih; }

        var svg = el('svg', { width: '100%', height: H, viewBox: '0 0 ' + W + ' ' + H, role: 'img' });
        // gridlines + y labels (5 ticks)
        for (var g = 0; g <= 4; g++) {
            var gv = min + (niceMax - min) * g / 4, gy = Y(gv);
            svg.appendChild(el('line', { x1: padL, y1: gy, x2: W - padR, y2: gy, stroke: '#EEF2F7', 'stroke-width': 1 }));
            var yt = txt(padL - 8, gy + 3, formatNum(gv), { 'text-anchor': 'end' });
            svg.appendChild(yt);
        }
        // x labels (max 8)
        var step = Math.max(1, Math.ceil(labels.length / 8));
        labels.forEach(function (lb, i) {
            if (i % step === 0 || i === labels.length - 1) {
                svg.appendChild(txt(X(i), H - 8, lb, {}));
            }
        });
        // series
        series.forEach(function (s, si) {
            var color = s.color || PALETTE[si % PALETTE.length];
            var pts = s.data.map(function (v, i) { return [X(i), Y(isFinite(v) ? v : min)]; });
            var d = 'M' + pts.map(function (p) { return p[0].toFixed(1) + ' ' + p[1].toFixed(1); }).join(' L');
            // area fill
            var areaD = d + ' L' + pts[pts.length - 1][0].toFixed(1) + ' ' + (padT + ih) + ' L' + pts[0][0].toFixed(1) + ' ' + (padT + ih) + ' Z';
            svg.appendChild(el('path', { d: areaD, fill: color, opacity: '0.10' }));
            svg.appendChild(el('path', { d: d, fill: 'none', stroke: color, 'stroke-width': '2.2', 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }));
            pts.forEach(function (p) { svg.appendChild(el('circle', { cx: p[0], cy: p[1], r: 2.4, fill: '#fff', stroke: color, 'stroke-width': 1.6 })); });
        });
        // legend
        if (series.length > 1) {
            var lx = padL;
            series.forEach(function (s, si) {
                var color = s.color || PALETTE[si % PALETTE.length];
                svg.appendChild(el('rect', { x: lx, y: 2, width: 10, height: 10, rx: 2, fill: color }));
                var lt = txt(lx + 15, 11, s.name || '', { 'text-anchor': 'start', 'font-size': '11', fill: '#475569' });
                svg.appendChild(lt);
                lx += 15 + s.name.length * 7 + 18;
            });
        }
        container.appendChild(svg);
        // tooltip
        if (opts.tooltip !== false) attachTooltip(container, svg, labels, series, X, Y);
    }

    function formatNum(v) {
        if (Math.abs(v) >= 1000) return v.toLocaleString('en-US', { maximumFractionDigits: 0 });
        return (+v.toFixed(2)).toString();
    }

    function attachTooltip(container, svg, labels, series, X, Y) {
        var tip = document.createElement('div');
        tip.style.cssText = 'position:absolute;pointer-events:none;background:#142B45;color:#fff;font-size:12px;padding:6px 10px;border-radius:8px;display:none;z-index:5;white-space:nowrap';
        container.style.position = 'relative';
        container.appendChild(tip);
        svg.addEventListener('mousemove', function (e) {
            var rect = svg.getBoundingClientRect();
            var scaleX = rect.width / svg.viewBox.baseVal.width || 1;
            var vx = (e.clientX - rect.left) / scaleX;
            var n = labels.length;
            var idx = Math.round(((vx - 44) / (rect.width / scaleX - 58)) * (n - 1));
            idx = Math.max(0, Math.min(n - 1, idx));
            var html = '<b>' + labels[idx] + '</b><br>';
            series.forEach(function (s, si) {
                var v = s.data[idx];
                if (isFinite(v)) html += '<span style="color:' + (s.color || PALETTE[si % PALETTE.length]) + '">●</span> ' + (s.name || '') + ': ' + formatNum(v) + '<br>';
            });
            tip.innerHTML = html;
            tip.style.display = 'block';
            tip.style.top = '8px';
            tip.style.insetInlineStart = Math.min(e.clientX - rect.left + 12, rect.width - 160) + 'px';
        });
        svg.addEventListener('mouseleave', function () { tip.style.display = 'none'; });
    }

    /* ---------- Donut ---------- */
    function donut(container, opts) {
        clean(container);
        var data = (opts.data || []).filter(function (d) { return d.value > 0; });
        if (!data.length) return emptyState(container);
        var total = data.reduce(function (a, d) { return a + d.value; }, 0);
        var size = opts.size || 190, r = size / 2 - 14, cx = size / 2, cy = size / 2;
        var svg = el('svg', { width: size, height: size, viewBox: '0 0 ' + size + ' ' + size, role: 'img' });
        var angle = -Math.PI / 2;
        data.forEach(function (d, i) {
            var frac = d.value / total, a2 = angle + frac * Math.PI * 2;
            var large = frac > 0.5 ? 1 : 0;
            var x1 = cx + r * Math.cos(angle), y1 = cy + r * Math.sin(angle);
            var x2 = cx + r * Math.cos(a2), y2 = cy + r * Math.sin(a2);
            var color = d.color || PALETTE[i % PALETTE.length];
            if (frac >= 0.9999) {
                svg.appendChild(el('circle', { cx: cx, cy: cy, r: r, fill: 'none', stroke: color, 'stroke-width': 26 }));
            } else {
                svg.appendChild(el('path', {
                    d: 'M' + cx + ' ' + cy + ' L' + x1.toFixed(2) + ' ' + y1.toFixed(2) + ' A' + r + ' ' + r + ' 0 ' + large + ' 1 ' + x2.toFixed(2) + ' ' + y2.toFixed(2) + ' Z',
                    fill: color
                }));
            }
            angle = a2;
        });
        // inner ring hole effect
        svg.appendChild(el('circle', { cx: cx, cy: cy, r: r - 16, fill: '#fff' }));
        var c1 = el('text', { x: cx, y: cy - 4, 'text-anchor': 'middle', 'font-size': '20', 'font-weight': '700', fill: '#142B45', 'font-family': 'Cairo, sans-serif' });
        c1.textContent = formatNum(opts.centerValue != null ? opts.centerValue : total);
        var c2 = el('text', { x: cx, y: cy + 14, 'text-anchor': 'middle', 'font-size': '10', fill: '#64748B' });
        c2.textContent = opts.centerLabel || '';
        svg.appendChild(c1); svg.appendChild(c2);
        var wrap = document.createElement('div');
        wrap.style.display = 'flex'; wrap.style.alignItems = 'center'; wrap.style.gap = '1.2rem'; wrap.style.flexWrap = 'wrap';
        var lg = document.createElement('div'); lg.style.flex = '1'; lg.style.minWidth = '140px';
        data.forEach(function (d, i) {
            var row = document.createElement('div');
            row.style.cssText = 'display:flex;align-items:center;gap:.5rem;font-size:.82rem;padding:.28rem 0;color:#334155';
            var pct = Math.round(d.value / total * 1000) / 10;
            row.innerHTML = '<span style="width:10px;height:10px;border-radius:3px;background:' + (d.color || PALETTE[i % PALETTE.length]) + ';flex:none"></span>' +
                '<span style="flex:1">' + d.label + '</span>' +
                '<b>' + formatNum(d.value) + '</b><span class="muted" style="min-width:44px;text-align:end">' + pct + '%</span>';
            lg.appendChild(row);
        });
        wrap.appendChild(svg); wrap.appendChild(lg);
        container.appendChild(wrap);
    }

    /* ---------- Horizontal bars ---------- */
    function bars(container, opts) {
        clean(container);
        var data = (opts.data || []).filter(function (d) { return isFinite(d.value) && d.value > 0; });
        if (!data.length) return emptyState(container);
        var max = Math.max.apply(null, data.map(function (d) { return d.value; }));
        var wrap = document.createElement('div');
        wrap.style.cssText = 'display:flex;flex-direction:column;gap:.55rem';
        data.forEach(function (d, i) {
            var color = d.color || PALETTE[i % PALETTE.length];
            var row = document.createElement('div');
            row.style.cssText = 'display:grid;grid-template-columns:130px 1fr 56px;align-items:center;gap:.7rem;font-size:.82rem';
            row.innerHTML =
                '<span style="color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="' + d.label + '">' + d.label + '</span>' +
                '<span style="background:#EEF2F7;border-radius:6px;height:16px;overflow:hidden;display:block">' +
                '<span style="display:block;height:100%;width:' + Math.max(2, d.value / max * 100) + '%;background:' + color + ';border-radius:6px"></span></span>' +
                '<b style="text-align:end;font-variant-numeric:tabular-nums">' + formatNum(d.value) + '</b>';
            wrap.appendChild(row);
        });
        container.appendChild(wrap);
    }

    /* ---------- Sparkline ---------- */
    function sparkline(container, opts) {
        var data = (opts.data || []).filter(isFinite);
        if (!data.length) return;
        var W = 120, H = 36, min = Math.min.apply(null, data), max = Math.max.apply(null, data);
        if (max === min) { max = min + 1; }
        var pts = data.map(function (v, i) {
            return (i / (data.length - 1 || 1)) * W + ',' + (H - 3 - ((v - min) / (max - min)) * (H - 6));
        }).join(' ');
        var color = opts.color || '#0D9488';
        container.innerHTML = '<svg viewBox="0 0 ' + W + ' ' + H + '" width="100%" height="' + H + '" preserveAspectRatio="none" style="display:block">' +
            '<polyline points="' + pts + '" fill="none" stroke="' + color + '" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>' +
            '<polygon points="0,' + H + ' ' + pts + ' ' + W + ',' + H + '" fill="' + color + '" opacity="0.10"/></svg>';
    }

    window.LabBICharts = { area: area, donut: donut, bars: bars, sparkline: sparkline, palette: PALETTE };
})();
