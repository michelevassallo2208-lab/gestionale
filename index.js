const express = require('express');
const path = require('path');

const app = express();

// Serve i file statici nella cartella "public"
app.use(express.static(path.join(__dirname, 'public')));

// Route principale: restituisce la landing page
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`Server in esecuzione su http://localhost:${port}`);
});
