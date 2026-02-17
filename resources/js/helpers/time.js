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

// Optional: returns both in a single object
export function tellerClock(date = new Date(), withSeconds = false) {
  return {
    date: formatDate(date),
    time: formatTime(date, withSeconds),
  }
}