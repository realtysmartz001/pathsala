const express = require('express');
const app = express();
const bodyParser = require('body-parser');
const mysql = require('mysql2');
const port = 3000;

// Middleware
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

// MySQL config
const db = mysql.createConnection({
    host: 'localhost',
    user: 'realtysm_pathshala',
    password: 'realtysmartzpathshala',
    database: 'realtysm_pathshala'
});
db.connect((err) => {
    if (err) {
        console.error('Connection failed: ' + err.stack);
        return;
    }
    console.log('✅ Connected to MySQL');
});

// ✅ Include routes from external files
const contactProcess = require('./contact_process');
const contactSuccess = require('./contact_success');

app.use('/', contactProcess);
app.use('/', contactSuccess);

// Start server
app.listen(port, () => {
    console.log(`🚀 Server running at http://localhost:${port}`);
});
