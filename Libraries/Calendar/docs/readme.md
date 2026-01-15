Claude says ...

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Download ICS File</title>
</head>
<body>
  <button onclick="downloadICS()">Download Calendar Event</button>

  <script>
    function downloadICS() {
      // Create the ICS file content
      const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Your Company//Your Product//EN
BEGIN:VEVENT
UID:${Date.now()}@yourdomain.com
DTSTAMP:${formatDate(new Date())}
DTSTART:${formatDate(new Date(2026, 0, 20, 14, 0))}
DTEND:${formatDate(new Date(2026, 0, 20, 15, 0))}
SUMMARY:Sample Meeting
DESCRIPTION:This is a sample calendar event
LOCATION:Conference Room A
END:VEVENT
END:VCALENDAR`;

      // Create a Blob from the ICS content
      const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
      
      // Create a temporary URL for the Blob
      const url = URL.createObjectURL(blob);
      
      // Create a temporary anchor element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.download = 'event.ics';
      document.body.appendChild(link);
      link.click();
      
      // Clean up
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }

    // Helper function to format dates in ICS format (YYYYMMDDTHHMMSS)
    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      const hours = String(date.getHours()).padStart(2, '0');
      const minutes = String(date.getMinutes()).padStart(2, '0');
      const seconds = String(date.getSeconds()).padStart(2, '0');
      return `${year}${month}${day}T${hours}${minutes}${seconds}`;
    }
  </script>
</body>
</html>


OR ...


  <button onclick="downloadICS()">Download Program Calendar</button>

  <script>
    function downloadICS() {
      // Create the ICS file content
      const icsContent = THE ICS CONTENTS;

      // Create a Blob from the ICS content
      const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
      
      // Create a temporary URL for the Blob
      const url = URL.createObjectURL(blob);
      
      // Create a temporary anchor element and trigger download
      const link = document.createElement('a');
      link.href = url;
      link.download = 'event.ics';
      document.body.appendChild(link);
      link.click();
      
      // Clean up
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }

  </script>


OR ...

  <button onclick="addToCalendar()">Add to Calendar</button>

  <script>
    function addToCalendar() {
      const icsContent = THE ICS CONTENTS;

      // Create a data URI
      const dataUri = 'data:text/calendar;charset=utf-8,' + encodeURIComponent(icsContent);
      
      // Open in a new window/tab - this will trigger the calendar app
      window.open(dataUri);
    }

  </script>
