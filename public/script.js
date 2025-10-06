document.addEventListener('DOMContentLoaded', function() {
    // Inizializza il calendario
    let calendarEl = document.getElementById('calendar');
    let calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      selectable: true,
      dateClick: function(info) {
        // Quando viene cliccata una data, controlla se è stata selezionata una persona
        let personSelect = document.getElementById('personSelect');
        let selectedValue = personSelect.value;
        if (selectedValue) {
          // Recupera il colore assegnato alla persona
          let selectedOption = personSelect.options[personSelect.selectedIndex];
          let personColor = selectedOption.getAttribute('data-color');
          // Aggiungi l'evento al calendario
          calendar.addEvent({
            title: selectedValue,
            start: info.dateStr,
            color: personColor
          });
        } else {
          alert("Per favore, seleziona una persona prima di aggiungere un evento.");
        }
      }
    });
    
    calendar.render();
  
    // Gestione del form per aggiungere una persona
    document.getElementById('addPersonForm').addEventListener('submit', function(e) {
      e.preventDefault();
      let personName = document.getElementById('personName').value.trim();
      let personColor = document.getElementById('personColor').value;
      
      if (personName) {
        // Aggiungi la nuova persona al menu a tendina
        let personSelect = document.getElementById('personSelect');
        let option = document.createElement('option');
        option.value = personName;
        option.text = personName;
        option.setAttribute('data-color', personColor);
        personSelect.appendChild(option);
        
        // Resetta i campi del form
        document.getElementById('personName').value = '';
        document.getElementById('personColor').value = '#ff0000';
      }
    });
  });
  