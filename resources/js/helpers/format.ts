export function formatLabel(str: string): string {
  return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
}

export function formatDateTime(dateTime: string): string {
    if (!dateTime) {
        return 'N/A';
    }
    
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
        }).format(dateTime ? new Date(dateTime) : new Date());
}

export function formatCurrency(value: number): string {
  return value.toLocaleString('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 })
}

export function formatNumber(value: string | number): string {
  const num = typeof value === 'string' ? parseFloat(value) : value;
  
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num || 0);
}

export function capitalizeFirstLetter(str: string): string {
    return str.charAt(0).toUpperCase() + str.slice(1);
}