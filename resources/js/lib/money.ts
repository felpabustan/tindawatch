export function formatPesos(centavos: number): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(centavos / 100);
}

export function pesosInputToNumber(value: string | number): number {
    return Number(value);
}
