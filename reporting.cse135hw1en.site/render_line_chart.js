const fs = require('fs');
const { ChartJSNodeCanvas } = require('chartjs-node-canvas');

async function main() {
  const inputPath = process.argv[2];
  const outputPath = process.argv[3];

  if (!inputPath || !outputPath) {
    console.error('Usage: node render_line_chart.js <input.json> <output.png>');
    process.exit(1);
  }

  const raw = fs.readFileSync(inputPath, 'utf8');
  const input = JSON.parse(raw);

  const width = 700;
  const height = 260;
  const chartJSNodeCanvas = new ChartJSNodeCanvas({
    width,
    height,
    backgroundColour: 'white'
  });

  const type = input.type || 'line';

  const configuration = {
    type,
    data: {
      labels: input.labels,
      datasets: [
        {
          label: input.datasetLabel || 'Value',
          data: input.values,
          borderWidth: 2,
          tension: type === 'line' ? 0.2 : 0,
          fill: false
        }
      ]
    },
    options: {
      responsive: false,
      animation: false,
      plugins: {
        legend: {
          display: true
        }
      },
      scales: {
        x: {
          ticks: {
            maxRotation: 0,
            autoSkip: true,
            maxTicksLimit: 10
          }
        },
        y: {
          beginAtZero: true
        }
      }
    }
  };

  const image = await chartJSNodeCanvas.renderToBuffer(configuration);
  fs.writeFileSync(outputPath, image);
}

main().catch(err => {
  console.error(err);
  process.exit(1);
});
