export function formatDate(date = new Date()) {
  return date.toLocaleDateString(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

export function formatTime(date = new Date(), withSeconds = false) {
  return date.toLocaleTimeString(undefined, {
    hour: '2-digit',
    minute: '2-digit',
    second: withSeconds ? '2-digit' : undefined,
  })
}

export function formatTicketDate(dateString) {
  if (!dateString) return 'N/A';
  
  const date = new Date(dateString);
  
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',   // "Mar"
    day: '2-digit',   // "11"
    year: 'numeric',   // "2026"
    hour: '2-digit',   // "04"
    minute: '2-digit', // "56"
    hour12: true       // "PM"
  }).format(date);
}

// Optional: returns both in a single object
export function tellerClock(date = new Date(), withSeconds = false) {
  return {
    date: formatDate(date),
    time: formatTime(date, withSeconds),
  }
}