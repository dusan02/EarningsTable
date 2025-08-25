const { createCanvas } = require('canvas');
const fs = require('fs');

// Create canvas
const canvas = createCanvas(32, 32);
const ctx = canvas.getContext('2d');

// Clear canvas
ctx.clearRect(0, 0, 32, 32);

// Background circle
ctx.beginPath();
ctx.arc(16, 16, 15, 0, 2 * Math.PI);
ctx.fillStyle = '#3b82f6';
ctx.fill();
ctx.strokeStyle = '#2563eb';
ctx.lineWidth = 1;
ctx.stroke();

// Chart bars
const bars = [
    {x: 8, y: 20, width: 2, height: 6},
    {x: 11, y: 16, width: 2, height: 10},
    {x: 14, y: 12, width: 2, height: 14},
    {x: 17, y: 18, width: 2, height: 8},
    {x: 20, y: 14, width: 2, height: 12},
    {x: 23, y: 10, width: 2, height: 16}
];

ctx.fillStyle = '#ffffff';
ctx.globalAlpha = 0.9;
bars.forEach(bar => {
    ctx.fillRect(bar.x, bar.y, bar.width, bar.height);
});

// Table grid lines
ctx.globalAlpha = 0.7;
ctx.strokeStyle = '#ffffff';
ctx.lineWidth = 1;
ctx.beginPath();
ctx.moveTo(6, 8);
ctx.lineTo(26, 8);
ctx.stroke();

ctx.globalAlpha = 0.5;
ctx.lineWidth = 0.5;
ctx.beginPath();
ctx.moveTo(6, 10);
ctx.lineTo(26, 10);
ctx.stroke();

ctx.beginPath();
ctx.moveTo(6, 12);
ctx.lineTo(26, 12);
ctx.stroke();

// Dollar sign
ctx.globalAlpha = 1;
ctx.fillStyle = '#ffffff';
ctx.font = 'bold 6px Arial';
ctx.textAlign = 'center';
ctx.fillText('$', 16, 7);

// Trend arrow
ctx.strokeStyle = '#10b981';
ctx.lineWidth = 1.5;
ctx.lineCap = 'round';
ctx.lineJoin = 'round';
ctx.beginPath();
ctx.moveTo(24, 6);
ctx.lineTo(26, 4);
ctx.lineTo(28, 6);
ctx.stroke();

// Save as PNG
const buffer = canvas.toBuffer('image/png');
fs.writeFileSync('favicon.png', buffer);

console.log('✅ Favicon generated successfully as favicon.png');
console.log('📁 File size:', buffer.length, 'bytes');
