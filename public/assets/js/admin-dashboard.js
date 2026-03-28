(() => {
  const root = document.querySelector("[data-admin-dashboard]");
  const payloadNode = document.querySelector("[data-admin-dashboard-payload]");

  if (!root || !payloadNode || typeof window.Chart === "undefined") {
    return;
  }

  let payload = {};

  try {
    payload = JSON.parse(payloadNode.textContent || "{}");
  } catch (_error) {
    payload = {};
  }

  const makeChart = (selector, config) => {
    const canvas = root.querySelector(selector);

    if (!canvas) {
      return;
    }

    const context = canvas.getContext("2d");

    if (!context) {
      return;
    }

    new window.Chart(context, config);
  };

  const baseOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        labels: {
          color: "#4c6380",
          boxWidth: 12,
          font: {
            family: "'Manrope', sans-serif",
            size: 12,
            weight: "600",
          },
        },
      },
      tooltip: {
        backgroundColor: "#11203b",
        titleFont: {
          family: "'Sora', sans-serif",
          weight: "700",
        },
        bodyFont: {
          family: "'Manrope', sans-serif",
        },
      },
    },
  };

  const doughnutOptions = {
    ...baseOptions,
    maintainAspectRatio: true,
    aspectRatio: 1,
  };

  makeChart("[data-users-chart]", {
    type: "doughnut",
    data: {
      labels: payload.user_roles?.labels || [],
      datasets: [
        {
          data: payload.user_roles?.values || [],
          backgroundColor: ["#1d4ed8", "#0f766e", "#f59e0b"],
          borderWidth: 0,
          hoverOffset: 10,
        },
      ],
    },
    options: {
      ...doughnutOptions,
      cutout: "68%",
    },
  });

  makeChart("[data-category-chart]", {
    type: "bar",
    data: {
      labels: payload.product_categories?.labels || [],
      datasets: [
        {
          data: payload.product_categories?.values || [],
          borderRadius: 12,
          backgroundColor: "#2563eb",
          maxBarThickness: 28,
        },
      ],
    },
    options: {
      ...baseOptions,
      plugins: {
        ...baseOptions.plugins,
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          ticks: {
            color: "#6b7c93",
            font: {
              family: "'Manrope', sans-serif",
              weight: "600",
            },
          },
          grid: {
            display: false,
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            color: "#6b7c93",
          },
          grid: {
            color: "rgba(148, 163, 184, 0.18)",
          },
        },
      },
    },
  });

  makeChart("[data-order-status-chart]", {
    type: "doughnut",
    data: {
      labels: payload.order_statuses?.labels || [],
      datasets: [
        {
          data: payload.order_statuses?.values || [],
          backgroundColor: ["#2563eb", "#0f766e", "#f59e0b", "#7c3aed", "#ef4444"],
          borderWidth: 0,
          hoverOffset: 8,
        },
      ],
    },
    options: {
      ...doughnutOptions,
      cutout: "64%",
    },
  });

  makeChart("[data-weekly-orders-chart]", {
    type: "line",
    data: {
      labels: payload.weekly_orders?.labels || [],
      datasets: [
        {
          data: payload.weekly_orders?.values || [],
          borderColor: "#1d4ed8",
          backgroundColor: "rgba(37, 99, 235, 0.12)",
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 5,
          pointBackgroundColor: "#1d4ed8",
        },
      ],
    },
    options: {
      ...baseOptions,
      plugins: {
        ...baseOptions.plugins,
        legend: {
          display: false,
        },
      },
      scales: {
        x: {
          ticks: {
            color: "#6b7c93",
            font: {
              family: "'Manrope', sans-serif",
              weight: "600",
            },
          },
          grid: {
            display: false,
          },
        },
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0,
            color: "#6b7c93",
          },
          grid: {
            color: "rgba(148, 163, 184, 0.18)",
          },
        },
      },
    },
  });
})();
