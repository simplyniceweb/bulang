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
                    @page { size: 58mm auto; margin: 0mm; }
                    body { 
                        margin: 0; padding: 0; width: 58mm; 
                        font-family: 'Arial', sans-serif;
                        text-align: center;
                        color: #000;
                    }
                    .receipt { width: 58mm; padding: 2mm; box-sizing: border-box; }
                    
                    /* Visual Dividers */
                    .hr-thick { border-bottom: 2px solid black; margin: 4px 0; }
                    .hr-dashed { border-bottom: 1px dashed black; margin: 6px 0; }

                    /* Typography Scale */
                    .font-huge { font-size: 28px; line-height: 1.1; font-weight: bold; }
                    .font-large { font-size: 20px; line-height: 1.2; font-weight: bold; }
                    .font-mid { font-size: 16px; font-weight: bold; }
                    .font-label { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }

                    /* Side Highlight (Inverted) */
                    .side-box {  
                        color: #000; 
                        padding: 4px; 
                        margin: 4px 0;
                        font-size: 30px;
                        font-weight: bold;
                    }

                    .barcode-container { margin: 5px 0; width: 100%; }
                    .barcode-container svg { max-width: 100%; height: auto; }
                </style>
            </head>
            <body>
                ${receiptContent}
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => { window.close(); }, 150);
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
        <!-- Brand -->
        <div class="font-large">JAYLORD SABONG</div>
        <div class="font-mid">GALLERA DE NATO</div>
        
        <div class="hr-thick"></div>

        <!-- Teller and Date (Enlarged) -->
        <div class="font-label">TELLER</div>
        <div class="font-mid" style="margin-bottom: 4px;">{{ ticket.teller_name }}</div>
        
        <div class="font-label">DATE / TIME</div>
        <div class="font-mid">{{ ticket.date }}</div>

        <div class="hr-dashed"></div>

        <!-- Fight Info -->
        <div class="font-label">FIGHT #</div>
        <div class="font-huge">{{ ticket.round_id }}</div>
        
        <div class="font-label" style="margin-top: 6px;">SIDE</div>
        <div class="side-box">{{ ticket.side?.toUpperCase() }}</div>

        <div class="hr-dashed"></div>

        <!-- Money Details -->
        <div style="display: flex; justify-content: space-between;" class="font-large">
            <span>BET:</span>
            <span>{{ ticket.amount }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 4px;" class="font-mid">
            <span>ODDS:</span>
            <span>{{ ticket.odds }}</span>
        </div>

        <div class="hr-thick" style="margin-top: 10px;"></div>

        <!-- Ticket Identification (Maximum Visibility) -->
        <div class="font-label">TICKET NUMBER</div>
        <div class="font-huge">{{ ticket.ticket_number }}</div>

        <div class="barcode-container">
            <div v-html="ticket.barcode_html"></div>
        </div>

        <div class="font-mid" style="margin-top: 5px; border: 1px solid black; padding: 2px;">
            NO TICKET, NO PAYOUT
        </div>
    </div>
</template>