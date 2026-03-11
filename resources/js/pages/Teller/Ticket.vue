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
}

const props = defineProps<{
    ticket: Ticket
}>();

const printReceipt = () => {
    const receiptElement = document.getElementById('receipt-print-area');
    if (!receiptElement) return;

    const receiptContent = receiptElement.outerHTML;
    
    // Using a Blob URL for the print window is often more stable in local intranets
    const printWindow = window.open('', '_blank', 'width=400,height=700');
    
    if (printWindow) {
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>TKT-${props.ticket.ticket_number}</title>
                <meta charset="UTF-8">
                <style>
                    @page { size: 72mm auto; margin: 0mm; }
                    body { 
                        margin: 0; padding: 0; width: 72mm; font-size: 14px; text-align: center;
                        font-family: 'Courier New', Courier, monospace;
                    }
                    .receipt { width: 72mm; padding: 1mm; box-sizing: border-box; }
                    .store-name { font-size: 16px; font-weight: bold; border-bottom: 1px solid black; margin-bottom: 2px; }
                    .header-row { display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid black; margin-top: 2px; }
                    .item-row { display: flex; justify-content: space-between; margin: 2px 0; font-size: 16px; }
                    .qr-code { margin: 5mm auto; display: block; }
                    /* Critical for thermal printers: sharp edges */
                    canvas { image-rendering: pixelated; }
                    .qr-code { display: flex; flex-direction: column; justify-content: center; align-items: center; width: 100%; margin: 15mm 0;}
                    #qrcode { display: block; margin: 10px auto; }
                    #qrcode img, #qrcode canvas { margin: 10px auto !important; display: block; }
                </style>
                <script src="/js/qrcode.min.js"><\/script>
            </head>
            <body>
                ${receiptContent}
                <script>
                    window.onload = function() {
                        const qrDiv = document.querySelector('#qrcode');
                        if (qrDiv) {
                            qrDiv.innerHTML = '';
                            new QRCode(qrDiv, {
                                text: "${props.ticket.ticket_number}",
                                width: 128,
                                height: 128,
                                correctLevel: QRCode.CorrectLevel.M
                            });
                        }
                        // Delay print slightly to allow QR to render
                        setTimeout(() => {
                            window.print();
                            window.close();
                        }, 100);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
};

// If you want to trigger it immediately when the component mounts
// (e.g., if this is a dedicated print-popup component)
// onMounted(() => printReceipt());

// Expose the method so the parent can call it via ref
defineExpose({ printReceipt });
</script>

<template>
    <div class="receipt" id="receipt-print-area">
        <div class="header">
            <div class="store-name">JAYLORD SABONG LIVE</div>
            <div style="font-size: 14px; font-weight: bold;">*** BET TICKET ***</div>
            <div style="font-size: 12px;">GALLERA DE NATO</div>
            <div style="font-size:12px;">{{ ticket.date }}</div>
        </div>

        <div class="meta" style="border-top: 1px dashed black; padding-top: 2px;">
            <div>TELLER: {{ ticket.teller_name }}</div>
            <div>ID: {{ ticket.ticket_number }}</div>
        </div>

        <div class="header-row">
            <span style="flex: 1; text-align: left;">FIGHT</span>
            <span style="flex: 1; text-align: center;">SIDE</span>
            <span style="flex: 1; text-align: right;">BET</span>
        </div>

        <div class="item-row">
            <span style="flex: 1; text-align: left;">#{{ ticket.round_id }}</span>
            <span style="flex: 1; text-align: center; font-weight: bold;">{{ ticket.side.toUpperCase() }}</span>
            <span style="flex: 1; text-align: right;">{{ ticket.amount }}</span>
        </div>

        <div class="meta" style="margin-top: 4px; font-size: 12px;">
            <div>PAYOUT RATIO: {{ ticket.odds }}</div>
            <div style="font-weight: bold;">POTENTIAL: {{ ticket.potential_payout }}</div>
        </div>

        <div id="qrcode" class="qr-code"></div>

        <div style="border-top: 2px dotted black; margin-top: 5px;">
            <small>Please keep this ticket.<br>No Ticket, No Payout.</small>
        </div>
    </div>
</template>