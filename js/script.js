// esempio di config ottimizzata
const commonConfig = {
    inputStream: {
      type: 'LiveStream',
      constraints: {
        facingMode: 'environment',
        width: { ideal: 1280 },
        height: { ideal: 720 }
      },
      area: {    // restringi ROI al centro
        top:    "25%", 
        right:  "10%", 
        left:   "10%", 
        bottom: "25%"
      },
      singleChannel: false // usa colore completo
    },
    numOfWorkers: navigator.hardwareConcurrency || 4,
    frequency: 10,        // numero di callback al secondo
    locate: true,         // abilita localizzazione automatica
    decoder: {
      readers: [
        "ean_reader",
        "code_128_reader",
        "upc_reader",
        "code_39_reader",
        "i2of5_reader"
      ],
      multiple: false
    },
    locate: true
  };
  
  // quando avvii la scansione:
  Quagga.init(commonConfig, err => {
    if (err) return console.error(err);
    Quagga.start();
  });
  
  // filtro per un solo evento di lettura
  Quagga.onDetected(data => {
    const code = data.codeResult.code;
    if (code) {
      // usa un debounce per evitare doppie chiamate
      if (!window._lastBarcode || window._lastBarcode !== code) {
        window._lastBarcode = code;
        document.getElementById('barcode').value = code;
        Quagga.stop();
        // ... chiudi interfaccia video ...
      }
    }
  });
  