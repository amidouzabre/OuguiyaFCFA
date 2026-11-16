/**
 * Format amounts and dates for West African Currency Exchange (Ouguiya MRU vs Franc CFA XOF)
 */

export function formatFcfa(amount: number | string | null | undefined): string {
    if (amount === null || amount === undefined || amount === '') return '0 FCFA';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(num)) return '0 FCFA';

    return new Intl.NumberFormat('fr-FR', {
        maximumFractionDigits: 0,
    }).format(num) + ' FCFA';
}

export function formatOuguiya(amount: number | string | null | undefined): string {
    if (amount === null || amount === undefined || amount === '') return '0 MRU';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(num)) return '0 MRU';

    return new Intl.NumberFormat('fr-FR', {
        maximumFractionDigits: 2,
    }).format(num) + ' MRU';
}

export function formatNumber(amount: number | string | null | undefined, decimals = 2): string {
    if (amount === null || amount === undefined || amount === '') return '0';
    const num = typeof amount === 'string' ? parseFloat(amount) : amount;
    if (isNaN(num)) return '0';

    return new Intl.NumberFormat('fr-FR', {
        maximumFractionDigits: decimals,
    }).format(num);
}

export function formatRate(rate: number | string | null | undefined): string {
    if (rate === null || rate === undefined || rate === '' || Number(rate) === 0) return '-';
    const num = typeof rate === 'string' ? parseFloat(rate) : rate;
    if (isNaN(num)) return '-';

    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    }).format(num);
}

export function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        }).format(d);
    } catch {
        return dateStr;
    }
}

export function formatShortDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return new Intl.DateTimeFormat('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(d);
    } catch {
        return dateStr;
    }
}
