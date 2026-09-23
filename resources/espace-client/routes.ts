const api = {
    payment: {
        strip: {
            store: 'api.payment.stripe.store',
            refund: 'api.payment.stripe.refund',
            success: 'api.payment.stripe.success',
        },
        paypal: {
            process: 'api.paypal.transaction.process',
            success: 'api.paypal.transaction.success',
            cancel: 'api.paypal.transaction.cancel',
        },
    },
    auth: {
        login: 'api.auth.login',
    },
    pages: {
        home: 'api.pages.home',
    },
};
const register = {
    monitor: {
        create: 'register.monitor.create',
        store: 'register.monitor.store',
    },
    student: {
        create: 'register.student.create',
        store: 'register.student.store',
    },
};

const contact = {
    index: 'contact.index',
    send: 'contact.send',
};
const formationPermisB = {
    index: 'formation-permis-b.index',
    pdf: 'formation-permis-b.pdf',
};

const legal = {
    mentions: 'legal.mention-legales',
    privacy: 'legal.politique-confidentialite',
    terms: 'legal.conditions-utilisation',
    sales: 'legal.conditions-vente',
};
const cpfForm = {
    index: 'forms-cpf.index',
    testPositionnement: 'forms-cpf.test.positionnement.index',
    testPositionnementPdf: 'forms-cpf.test.positionnement.pdf',
    contactFormationPage: 'forms-cpf.contact.formation.index',
    contactFormationPdf: 'forms-cpf.contact.formation.pdf',
    attestationPage: 'forms-cpf.attestation.honneur.index',
    attestationPdf: 'forms-cpf.attestation.honneur.pdf',
    attestationStore: 'forms-cpf.attestation.honneur.store',
};
export const routes = {
    home: 'home.index',
    videos: 'videos.index',
    agencies: 'agencies.index',
    code: 'code.index',
    offers: 'offers.index',
    checkout: 'checkout',
    CPF: 'cpf.index',
    login: 'login',
    legal,
    formationPermisB,
    cpfForm,
    api,
    register,
    contact,
};
