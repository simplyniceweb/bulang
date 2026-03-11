<script setup lang="ts">
import { formatNumber } from '@/helpers/format';
import { formatTicketDate } from '@/helpers/time';

const props = defineProps<{
    ticket: any
}>();

const printReceipt = () => {
    const receiptElement = document.getElementById('payout-print-area');
    if (!receiptElement) return;

    const printWindow = window.open('', '_blank', 'width=400,height=700');
    if (!printWindow) return;

    printWindow.document.write(`
        <html>
        <head>
            <title>PAYOUT-${props.ticket.ticket_number}</title>
            <style>
                @page { size: 72mm auto; margin: 0mm; }
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    width: 72mm; padding: 2mm; text-align: center; font-size: 14px; 
                }
                ${Array.from(document.styleSheets)
                    .map(sheet => {
                        try { return Array.from(sheet.cssRules).map(rule => rule.cssText).join(''); } 
                        catch (e) { return ''; }
                    }).join('')}
            </style>
        </head>
        <body>
            ${receiptElement.outerHTML}
            <script>
                function startPrint() {
                    window.print();
                    // Give the print spooler time to catch the document
                    setTimeout(() => {
                        window.close();
                    }, 300);
                }

                // Try to print as soon as possible
                if (document.readyState === 'complete') {
                    startPrint();
                } else {
                    window.addEventListener('load', startPrint);
                }
            <\/script>
        </body>
        </html>
    `);

    printWindow.document.close();
};

defineExpose({ printReceipt });
</script>

<template>
    <div id="payout-print-area" class="payout-receipt" style="width: 72mm;">
        <div style="font-weight: bold; font-size: 16px; border-bottom: 2px solid black;">JAYLORD SABONG LIVE</div>
        <div style="font-size: 18px; font-weight: bold; margin: 5px 0;">*** PAYOUT SLIP ***</div>
        <div style="font-size: 12px;">GALLERA DE NATO</div>
        <div style="font-size: 12px; border-bottom: 1px dashed black; margin: 5px 0;">{{ formatTicketDate(ticket.claimed_at) }}</div>
        
        <div style="display: flex; justify-content: space-between;">
            <span>TICKET:</span> 
            <span>{{ ticket.ticket_number }}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>FIGHT #:</span> 
            <span>{{ ticket.round_id }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
            <span>WINNER:</span> 
            <strong style="text-transform: uppercase;">{{ ticket.round?.winner }}</strong>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
            <span>TELLER:</span> 
            <strong style="text-transform: uppercase;">{{ ticket.teller?.name }}</strong>
        </div>
        <div style="border-top: 1px dashed black; margin-top: 5px; padding-top: 5px; font-size: 18px; font-weight: bold; display: flex; justify-content: space-between;">
            <span>PAID:</span>
            <span>₱{{ formatNumber(ticket.potential_payout) }}</span>
        </div>

        <div style="font-size: 10px; margin-top: 10px; border-top: 1px solid black; padding-top: 5px; text-align: center;">
            TELLER: {{ ticket.paid_by?.name || 'SYSTEM' }}<br>
            REF ID: {{ ticket.id }}<br>
            *** TRANSACTION COMPLETED ***
        </div>
    </div>
</template>