export type Client = {
    id: number;
    name: string;
    telephone?: string | null;
    address?: string | null;
    notes?: string | null;
    solde_ouguiya: number | string;
    solde_fcfa: number | string;
    transactions_count?: number;
    created_at?: string;
    updated_at?: string;
    transactions?: Transaction[];
};

export type TransactionType =
    | 'vente'
    | 'achat'
    | 'remboursement_fcfa'
    | 'remboursement_ouguiya'
    | 'depot'
    | 'autre';

export type Transaction = {
    id: number;
    client_id: number;
    date_operation: string;
    numero_operation: number;
    type_operation: TransactionType;
    purchase_source_id?: number | null;
    description?: string | null;
    ouguiya: number | string;
    payement_ouguiya: number | string;
    solde_ouguiya_ligne: number | string;
    fcfa: number | string;
    payement_fcfa: number | string;
    solde_fcfa_ligne: number | string;
    rate?: number | string | null;
    taux_achat?: number | string | null;
    gain: number | string;
    created_by?: number | null;
    created_at?: string;
    updated_at?: string;
    client?: Client;
    purchase_source?: Transaction | null;
    creator?: {
        id: number;
        name: string;
    };
};

export type DashboardMetrics = {
    total_creances_fcfa: number;
    total_avoirs_fcfa: number;
    total_solde_ouguiya: number;
    total_gains: number;
    gains_this_month: number;
    total_volume_fcfa: number;
    total_volume_ouguiya: number;
    total_clients: number;
    clients_en_dette: number;
};

export type MonthlyStat = {
    month: string;
    label: string;
    gain: number;
    volume_fcfa: number;
    volume_mru: number;
};
