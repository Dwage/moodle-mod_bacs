/* global BacsUtils */
window.BacsUtils = {
  COLORS: [
    "#2196F3", "#F44336", "#4CAF50", "#FFC107", "#9C27B0",
    "#00BCD4", "#FF9800", "#E91E63", "#3F51B5", "#CDDC39",
    "#795548", "#607D8B", "#673AB7", "#009688", "#FF5722",
    "#8BC34A", "#03A9F4", "#E040FB", "#FFEB3B", "#9E9E9E",
  ],

  formatTime: (totalSeconds) => {
    totalSeconds = Math.max(0, Math.floor(totalSeconds));
    const days = Math.floor(totalSeconds / 86400);
    totalSeconds %= 86400;
    const hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    const hStr = hours < 10 ? "0" + hours : hours;
    const mStr = minutes < 10 ? "0" + minutes : minutes;
    const sStr = seconds < 10 ? "0" + seconds : seconds;

    if (days > 0) {
 return `${days}d ${hStr}:${mStr}`;
}
    if (hours > 0) {
 return `${hStr}:${mStr}:${sStr}`;
}
    return `${mStr}:${sStr}`;
  },

  formatFullDate: (timestampSec) => {
    const d = new Date(timestampSec * 1000);
    return d.toLocaleDateString("en-US", {year: "numeric", month: "short", day: "2-digit"}) + ", " +
           d.toLocaleTimeString("en-US", {hour: "2-digit", minute: "2-digit", hour12: false});
  },

  getContestProgress: (timestampSec, startTime, endTime) => {
    if (timestampSec > endTime) {
        return {percent: 100, text: "[Upsolving]", color: "#6b7280", isUpsolving: true};
    }
    const duration = endTime - startTime;
    let p = 0;
    if (duration > 0) {
        p = ((timestampSec - startTime) / duration) * 100;
        p = Math.max(0, Math.min(100, p));
    }
    let color = "#10b981";
    if (p >= 50) {
 color = "#f59e0b";
}
    if (p >= 85) {
 color = "#ef4444";
}
    return {percent: p, text: `${p.toFixed(0)}%`, color: color, isUpsolving: false};
  },

  getUserComparator: (mode) => (a, b) => {
    const valA = (v) => (isNaN(v) || v === null || v === undefined ? 0 : v);
    const compare = (key, asc = true) => {
      const dir = asc ? 1 : -1;
      if (valA(a[key]) < valA(b[key])) {
 return -1 * dir;
}
      if (valA(a[key]) > valA(b[key])) {
 return 1 * dir;
}
      return 0;
    };
    if (mode === 0) {
 return compare("points", false) || compare("name");
}
    if (mode === 1) {
 return compare("solved", false) || compare("penalty") || compare("lastImprovement") || compare("name");
}
    return compare("points", false) || compare("solved", false)
    || compare("penalty") || compare("lastImprovement") || compare("name");
  },

  // ==============================================================================
  // UI & CHART FACTORIES
  // ==============================================================================

  createEmptyState: (id, parentNode, insertBeforeNode, iconClass, titleText, descText) => {
    let el = document.getElementById(id);
    if (!el) {
        el = document.createElement('div');
        el.id = id;
        el.style.cssText = `
            background-color: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 12px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            min-height: 400px; color: #6c757d; text-align: center; padding: 2rem;
            animation: fadeIn 0.3s ease-in-out; display: none;
        `;
        if (parentNode) {
            if (insertBeforeNode) {
 parentNode.insertBefore(el, insertBeforeNode);
} else {
 parentNode.appendChild(el);
}
        }
    }
    const iconColor = iconClass.includes("rocket") || iconClass.includes("graph") ? "#0d6efd" : "#6c757d";

    el.innerHTML = `
        <i class="bi ${iconClass}" style="font-size: 4.5rem; opacity: 0.3; margin-bottom: 1rem; color: ${iconColor};"></i>
        <h4 style="font-weight: 600; color: #495057;">${titleText}</h4>
        <p style="max-width: 450px; margin: 0 auto; font-size: 0.95rem;">${descText}</p>
    `;
    return el;
  },

  createChartLayout: (canvasId, prefix, emptyIcon, emptyTitle, emptyDesc) => {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
 return null;
}

    let emptyStateContainer = BacsUtils.createEmptyState(
        `${prefix}-empty`, canvas.parentNode, canvas, emptyIcon, emptyTitle, emptyDesc
    );

    let flexContainer = document.getElementById(`${prefix}-flex`);
    let legendContainer = document.getElementById(`custom-${prefix}-legend`);

    if (!flexContainer) {
      flexContainer = document.createElement('div');
      flexContainer.id = `${prefix}-flex`;
      flexContainer.style.cssText = `display: flex; flex-direction: row; 
      align-items: stretch; gap: 20px; min-height: 600px; margin-top: 10px;`;

      const canvasDiv = document.createElement('div');
      canvasDiv.style.cssText = `flex: 1; min-width: 0; position: relative;`;

      legendContainer = document.createElement('div');
      legendContainer.id = `custom-${prefix}-legend`;
      legendContainer.className = 'custom-scroll';
      legendContainer.style.cssText = `width: 240px; overflow-y: auto; max-height: 600px; padding-right: 10px;`;

      canvas.parentNode.insertBefore(flexContainer, canvas);
      canvasDiv.appendChild(canvas);
      flexContainer.appendChild(canvasDiv);
      flexContainer.appendChild(legendContainer);
    }

    return {flexContainer, legendContainer, emptyStateContainer};
  },

  createLegendItem: (container, colorHex, title, rightText, onClickCallback) => {
    const item = document.createElement('div');
    item.className = 'custom-legend-item d-flex align-items-center mb-2 px-2 py-1 rounded';
    item.style.cursor = 'pointer';
    item.style.transition = 'background-color 0.2s';
    item.innerHTML = `
      <span style="display:inline-block; width:10px; height:10px; 
      border-radius:50%; background-color:${colorHex}; margin-right:10px; flex-shrink:0;"></span>
      <span style="font-size:0.85rem; color:#4b5563; font-weight:500; 
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${title}">${title}</span>
      <span style="margin-left:auto; font-size:0.8rem; font-weight:700; color:#111827;">${rightText}</span>`;

    item.addEventListener('mouseenter', () => {
      item.style.backgroundColor = '#f3f4f6';
    });
    item.addEventListener('mouseleave', () => {
      item.style.backgroundColor = 'transparent';
    });
    item.addEventListener('click', onClickCallback);
    container.appendChild(item);
  },

  renderStatsBadges: (container, total, ok, rate) => {
    if (!container) {
 return;
}
    container.innerHTML = `
        <div style="display: flex; gap: 15px; justify-content: center; align-items: center; margin-bottom: 10px;">
            <div style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; 
            padding: 6px 12px; color: #4b5563; font-size: 0.9rem; font-weight: 600;">
                TOTAL: <span style="color: #111827;">${total}</span>
            </div>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; 
            padding: 6px 12px; color: #047857; font-size: 0.9rem; font-weight: 600;">
                OK: <span style="color: #065f46;">${ok}</span>
            </div>
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; 
            padding: 6px 12px; color: #1d4ed8; font-size: 0.9rem; font-weight: 600;">
                SUCCESS RATE: <span style="color: #1e3a8a;">${rate}%</span>
            </div>
        </div>
    `;
  },

  createTimeWarper: (subs, startTime) => {
    let times = subs.map(s => (s.submit_time - startTime) * 1000).filter(t => t >= 0);
    times = [...new Set(times)].sort((a, b) => a - b);

    let totalActiveTime = times.length > 1 ? times[times.length - 1] - times[0] : 0;
    let MAX_GAP = Math.max(3600000, Math.min(12 * 3600000, totalActiveTime * 0.05));
    if (times.length === 0) {
 MAX_GAP = Infinity;
}

    let points = [{r: 0, v: 0}];
    let currentV = 0,
currentR = 0;

    for (let i = 0; i < times.length; i++) {
        let gap = times[i] - currentR;
        if (gap > 0) {
            let vGap = Math.min(gap, MAX_GAP);
            currentV += vGap;
            currentR = times[i];
            points.push({r: currentR, v: currentV});
        }
    }

    return {
        r2v: (rTime) => {
            if (rTime <= 0) {
 return 0;
}
            for (let i = 1; i < points.length; i++) {
                if (rTime <= points[i].r) {
                    let p1 = points[i - 1],
p2 = points[i];
                    return p1.v + (rTime - p1.r) * ((p2.v - p1.v) / (p2.r - p1.r));
                }
            }
            let last = points[points.length - 1];
            return last.v + Math.min(rTime - last.r, MAX_GAP);
        },
        v2r: (vTime) => {
            if (vTime <= 0) {
 return 0;
}
            for (let i = 1; i < points.length; i++) {
                if (vTime <= points[i].v) {
                    let p1 = points[i - 1],
p2 = points[i];
                    return p1.r + (vTime - p1.v) * ((p2.r - p1.r) / (p2.v - p1.v));
                }
            }
            let last = points[points.length - 1];
            return last.r + (vTime - last.v);
        }
    };
  },

  toggleDatasetFocus: (chart, datasetIndex, legendContainerId, getFocusedRadiusFn) => {
    const focusedIndices = [];
    chart.data.datasets.forEach((d, i) => {
 if (d.borderWidth === 4) {
 focusedIndices.push(i);
}
});

    if (focusedIndices.length === 0) {
 focusedIndices.push(datasetIndex);
} else {
      const pos = focusedIndices.indexOf(datasetIndex);
      if (pos !== -1) {
 focusedIndices.splice(pos, 1);
} else {
 focusedIndices.push(datasetIndex);
}
    }

    if (focusedIndices.length === 0) {
      chart.data.datasets.forEach((d, i) => {
        const colorNormal = d.baseColor + "E6";
        d.borderColor = colorNormal;
        d.borderWidth = 2;
        d.pointRadius = d.data.map(p => p.isLast ? 4 : 0);
        d.pointBackgroundColor = d.data.map(() => "#FFFFFF");
        d.pointBorderColor = d.data.map(() => colorNormal);
        chart.getDatasetMeta(i).order = 0;
      });
      document.querySelectorAll(`#${legendContainerId} .custom-legend-item`).forEach(el => { el.style.opacity = '1'; });
    } else {
      chart.data.datasets.forEach((d, i) => {
        if (focusedIndices.includes(i)) {
          d.borderColor = d.baseColor;
          d.borderWidth = 4;
          d.pointRadius = d.data.map(p => getFocusedRadiusFn(p));
          d.pointBackgroundColor = d.data.map(() => d.baseColor);
          d.pointBorderColor = d.data.map(() => "#FFFFFF");
          chart.getDatasetMeta(i).order = -1;
        } else {
          d.borderColor = d.baseColor + "1A";
          d.borderWidth = 1.5;
          d.pointRadius = 0;
          chart.getDatasetMeta(i).order = 0;
        }
      });
      document.querySelectorAll(`#${legendContainerId} .custom-legend-item`).forEach((el, i) => {
          el.style.opacity = focusedIndices.includes(i) ? '1' : '0.3';
      });
    }
    chart.update();
  },

  getLineClickPlugin: (onHitCallback) => {
    return {
      id: "lineClick",
      afterEvent(chart, args) {
        if (args.event.type !== "click") {
 return;
}
        const clickX = args.event.x,
clickY = args.event.y,
chartArea = chart.chartArea;

        if (clickX < chartArea.left || clickX > chartArea.right || clickY < chartArea.top || clickY > chartArea.bottom) {
 return;
}

        let closestDatasetIndex = -1;
        let minDistance = Infinity;

        chart.data.datasets.forEach((dataset, i) => {
          const meta = chart.getDatasetMeta(i);
          if (meta.hidden) {
 return;
}

          for (let j = 0; j < meta.data.length - 1; j++) {
            const p1 = meta.data[j],
p2 = meta.data[j + 1];
            if (!p1 || !p2 || p1.skip || p2.skip) {
 continue;
}

            const l2 = Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2);
            let dist;
            if (l2 === 0) {
                dist = Math.hypot(clickX - p1.x, clickY - p1.y);
            } else {
                let t = ((clickX - p1.x) * (p2.x - p1.x) + (clickY - p1.y) * (p2.y - p1.y)) / l2;
                t = Math.max(0, Math.min(1, t));
                const projX = p1.x + t * (p2.x - p1.x);
                const projY = p1.y + t * (p2.y - p1.y);
                dist = Math.hypot(clickX - projX, clickY - projY);
            }

            if (dist < minDistance) {
                minDistance = dist;
                closestDatasetIndex = i;
            }
          }
        });

        if (minDistance < 15 && closestDatasetIndex !== -1) {
            onHitCallback(chart, closestDatasetIndex);
            args.changed = true;
        }
      }
    };
  },

  getTooltipBaseConfig: (customCallbacks) => {
      return {
          backgroundColor: 'rgba(255, 255, 255, 0.95)',
          titleColor: '#1f2937', bodyColor: '#4b5563', borderColor: '#e5e7eb', borderWidth: 1,
          titleFont: {size: 13, weight: 'bold', family: "'Inter', sans-serif"},
          bodyFont: {size: 12, family: "'Inter', sans-serif"},
          padding: 12, cornerRadius: 6, displayColors: true, boxPadding: 6,
          callbacks: customCallbacks
      };
  }
};