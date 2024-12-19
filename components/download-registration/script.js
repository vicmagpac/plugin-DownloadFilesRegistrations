
app.component('download-registration', {
    template: $TEMPLATES['download-registration'],

    setup(props) {
        const text = Utils.getTexts('download-registration');
        const messages = useMessages();
        return { text, messages }
    },
    props: {
        entity: {
            type: Entity,
            required: true
        }
    },
    data() {
        return {
            processing: false,
        }
    },
    methods: {
        download() {
            this.processing = true;
            let api = new API();

            let args = {
                entity: this.entity.opportunity.id,
                registrationId: this.entity.id
            };

            let url = Utils.createUrl('opportunity', 'registrationsDownload', args);

            api.GET(url).then(res => {
                if (!res.ok) {
                    throw new Error('Erro ao processar o arquivo.');
                }
            
                let contentDisposition = res.headers.get('Content-Disposition');
                let fileName = 'arquivo.zip'; 
            
                if (contentDisposition && contentDisposition.includes('filename=')) {
                    fileName = contentDisposition.split('filename=')[1].split(';')[0].replace(/"/g, '');
                }
            
                return res.blob().then(blob => ({ blob, fileName }));
            }).then(({ blob, fileName }) => {
                let downloadUrl = window.URL.createObjectURL(blob);
            
                let a = document.createElement('a');
                a.href = downloadUrl;
                a.download = fileName; 
                document.body.appendChild(a);
                a.click();
            
                a.remove();
                window.URL.revokeObjectURL(downloadUrl);
                this.processing = false;
            }).catch(error => {
                console.error('Erro:', error);
                this.processing = false;
            });
        },
    },
});
