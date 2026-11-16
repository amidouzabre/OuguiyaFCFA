export function getWhatsAppUrl(
    phone: string | null | undefined,
    message: string,
): string {
    let cleanPhone = phone?.replace(/\D/g, '') ?? '';

    if (cleanPhone.startsWith('00')) {
        cleanPhone = cleanPhone.slice(2);
    } else if (cleanPhone.length === 10 && cleanPhone.startsWith('0')) {
        cleanPhone = `225${cleanPhone}`;
    }

    const encodedMessage = encodeURIComponent(message);

    return cleanPhone
        ? `https://wa.me/${cleanPhone}?text=${encodedMessage}`
        : `https://wa.me/?text=${encodedMessage}`;
}
