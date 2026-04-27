<script setup lang="ts">
interface Ticket {
    ticket_number: string;
    round_id: number;
    side: string;
    amount: number | string;
    potential_payout: number | string;
    odds: number | string;
    teller_name: string;
    date: string;
    barcode_html: string;
}

const props = defineProps<{
    ticket: Ticket
}>();

const printReceipt = () => {
    const receiptElement = document.getElementById('receipt-print-area');
    if (!receiptElement) return;

    const receiptContent = receiptElement.outerHTML;
    
    const printWindow = window.open('', '_blank', 'width=400,height=700');
    
    if (printWindow) {
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>TKT-${props.ticket.ticket_number}</title>
                <meta charset="UTF-8">
                <style>
                    /* Optimized for 58mm thermal paper */
                    @page { size: 58mm auto; margin: 0mm; }
                    body { 
                        margin: 0; padding: 0; width: 58mm; font-size: 12px; text-align: center;
                        font-family: 'Courier New', Courier, monospace;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .receipt { width: 58mm; padding: 2mm; box-sizing: border-box; }
                    .store-name { font-size: 14px; font-weight: bold; border-bottom: 1px solid black; margin-bottom: 2px; }
                    .header-row { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid black; margin-top: 2px; }
                    .item-row { display: flex; justify-content: space-between; margin: 2px 0; font-size: 14px; }
                    
                    .barcode-container { 
                        margin: 4mm 0; 
                        width: 100%;
                        overflow: hidden;
                    }
                    /* Ensure the SVG scales to fit the container width automatically */
                    .barcode-container svg {
                        max-width: 100%;
                        height: auto;
                    }
                </style>
            </head>
            <body>
                ${receiptContent}
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => { window.close(); }, 100);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
};

defineExpose({ printReceipt });
</script>

<template>
    <div class="receipt" id="receipt-print-area">
        <div class="header">
            <div class="store-name">JAYLORD SABONG LIVE</div>
            <div style="font-size: 13px; font-weight: bold;">*** BET TICKET ***</div>
            <div style="font-size: 11px;">GALLERA DE NATO</div>
            <div style="font-size: 11px;">{{ ticket.date }}</div>
        </div>

        <div class="meta" style="border-top: 1px dashed black; padding-top: 2px; font-size: 11px;">
            <div>TELLER: {{ ticket.teller_name }}</div>
            <div>ID: {{ ticket.ticket_number }}</div>
        </div>

        <div class="header-row" style="font-size: 11px;">
            <span style="flex: 1; text-align: left;">FIGHT</span>
            <span style="flex: 1; text-align: center;">SIDE</span>
            <span style="flex: 1; text-align: right;">BET</span>
        </div>

        <div class="item-row">
            <span style="flex: 1; text-align: left;">#{{ ticket.round_id }}</span>
            <span style="flex: 1; text-align: center; font-weight: bold;">{{ ticket.side.toUpperCase() }}</span>
            <span style="flex: 1; text-align: right;">{{ ticket.amount }}</span>
        </div>

        <div class="meta" style="margin-top: 4px; font-size: 11px;">
            <div>PAYOUT RATIO: {{ ticket.odds }}</div>
            <div style="font-weight: bold; font-size: 13px;">POTENTIAL: {{ ticket.potential_payout }}</div>
        </div>

        <div class="barcode-container">
            <div v-html="ticket.barcode_html"></div>
        </div>

        <div style="border-top: 1px dotted black; margin-top: 5px; font-size: 10px;">
            <small>Please keep this ticket.<br>No Ticket, No Payout.</small>
        </div>
    </div>
</template>