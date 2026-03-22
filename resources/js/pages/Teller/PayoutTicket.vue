<script setup lang="ts">
import { formatNumber } from '@/helpers/format';
import { formatTicketDate } from '@/helpers/time';

const props = defineProps<{
    ticket: any
}>();

const printReceipt = () => {
    const receiptElement = document.getElementById('payout-print-area');
    if (!receiptElement) return;

    // Open a narrow window to mimic thermal paper width
    const printWindow = window.open('', '_blank', 'width=400,height=700');
    if (!printWindow) return;

    printWindow.document.write(`
        <html>
        <head>
            <title>PAYOUT-${props.ticket.ticket_number}</title>
            <style>
                /* Setup for 72mm Thermal Paper */
                @page { 
                    size: 72mm auto; 
                    margin: 0; 
                }
                body { 
                    margin: 0;
                    padding: 0;
                    width: 72mm;
                    display: flex;
                    flex-direction: column;
                    align-items: center; /* Centers the content div */
                    font-family: 'Courier New', Courier, monospace;
                    background-color: white;
                }
                #payout-print-area {
                    width: 66mm; /* Safety margin: 72mm paper - 6mm total margins */
                    padding: 4mm 0;
                    text-align: center;
                    color: black;
                }
                .flex-row {
                    display: flex;
                    justify-content: space-between;
                    width: 100%;
                    margin-top: 2px;
                }
                .border-top { border-top: 1px dashed black; }
                .border-bottom { border-bottom: 1px dashed black; }
                .bold { font-weight: bold; }
                .large { font-size: 18px; }
                
                /* Ensure colors don't bleed or disappear */
                * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            </style>
        </head>
        <body>
            <div id="payout-print-area">
                ${receiptElement.innerHTML}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    setTimeout(() => { window.close(); }, 500);
                };
            <\/script>
        </body>
        </html>
    `);

    printWindow.document.close();
};

defineExpose({ printReceipt });
</script>

<template>
    <div id="payout-print-area" style="display: none;">
        <div style="font-weight: bold; font-size: 16px; border-bottom: 2px solid black; padding-bottom: 4px; margin-bottom: 5px;">
            JAYLORD SABONG LIVE
        </div>
        
        <div style="font-size: 18px; font-weight: bold; margin: 5px 0;">
            *** {{ ticket.round?.status === 'cancelled' ? 'REFUND' : 'PAYOUT' }} SLIP ***
        </div>
        
        <div style="font-size: 12px;">GALLERA DE NATO</div>
        <div style="font-size: 12px; border-bottom: 1px dashed black; margin: 5px 0; padding-bottom: 5px;">
            {{ formatTicketDate(ticket.claimed_at || new Date()) }}
        </div>
        
        <div style="display: flex; justify-content: space-between; width: 100%;">
            <span>TICKET:</span> 
            <span>{{ ticket.ticket_number }}</span>
        </div>
        
        <div style="display: flex; justify-content: space-between; width: 100%;">
            <span>FIGHT #:</span> 
            <span>{{ ticket.round?.round_number || ticket.round_id }}</span>
        </div>

        <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 5px;">
            <span>WINNER:</span> 
            <strong style="text-transform: uppercase;">
                {{ ticket.round?.status === 'cancelled' ? 'CANCELLED' : (ticket.round?.winner || 'N/A') }}
            </strong>
        </div>

        <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 5px;">
            <span>BET ON:</span> 
            <strong style="text-transform: uppercase;">{{ ticket.side }}</strong>
        </div>

        <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 5px;">
            <span>AMOUNT:</span> 
            <strong>₱{{ formatNumber(ticket.amount) }}</strong>
        </div>

        <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 5px;">
            <span>ODDS:</span> 
            <strong>{{ ticket.odds }}</strong>
        </div>

        <div style="border-top: 1px dashed black; margin-top: 8px; padding-top: 8px; font-size: 20px; font-weight: bold; display: flex; justify-content: space-between; width: 100%;">
            <span>{{ ticket.round?.status === 'cancelled' ? 'REFUND' : 'PAID' }}:</span>
            <span>₱{{ formatNumber(ticket.potential_payout) }}</span>
        </div>

        <div style="font-size: 10px; margin-top: 15px; border-top: 1px solid black; padding-top: 8px; text-align: center; line-height: 1.4;">
            TELLER: {{ ticket.paid_by?.name || ticket.teller?.name || 'SYSTEM' }}<br>
            REF ID: {{ ticket.id }}<br>
            VALID ONLY AT THIS BRANCH<br>
            *** TRANSACTION COMPLETED ***
        </div>
    </div>
</template>

<style scoped>
/* Hidden in the actual browser view, but used by the print function */
#payout-print-area {
    font-family: 'Courier New', Courier, monospace;
    color: black;
    background: white;
}
</style>