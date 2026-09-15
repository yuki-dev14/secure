const fs = require('fs');

const content = fs.readFileSync('storage/logs/laravel.log', 'utf-8');
const lines = content.split('\n');

console.log("Total log lines:", lines.length);

const errors = [];
for (let i = 0; i < lines.length; i++) {
  if (lines[i].includes('ERROR') || lines[i].includes('Exception') || lines[i].includes('PDOException')) {
    errors.push(lines.slice(Math.max(0, i - 1), Math.min(lines.length, i + 6)).join('\n'));
  }
}

console.log("=== RECENT ERRORS FOUND IN LARAVEL.LOG ===");
console.log(errors.slice(-10).join('\n----------------------------------------\n'));
