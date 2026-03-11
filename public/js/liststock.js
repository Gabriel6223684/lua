import { Requests } from "./Requests.js";

// Variável global para a tabela
let tabela;

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    inicializarDataTable();
    inicializarSelect2();
    inicializarEventListeners();
});

// Inicializa o DataTables
function inicializarDataTable() {
    tabela = $('#tabela').DataTable({
        paging: true,
        lengthChange: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
        stateSave: true,
        select: true,
        processing: true,
        serverSide: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/2.3.7/i18n/pt-BR.json',
            searchPlaceholder: 'Digite sua pesquisa...'
        },
        ajax: {
            url: '/estoque/liststock',
            type: 'POST',
            data: function(d) {
                // Adiciona os filtros personalizados
                d.id_produto = $('#produto').val();
                d.tipo_movimento = $('#tipo_movimento').val();
                d.data_inicial = $('#data_inicial').val();
                d.data_final = $('#data_final').val();
                return d;
            }
        },
        columns: [
            { data: 0 }, // Data/Hora
            { data: 1 }, // Produto
            { data: 2 }, // Tipo
            { data: 3, className: 'text-center' }, // Entrada
            { data: 4, className: 'text-center' }, // Saída
            { data: 5, className: 'text-center' }, // Est. Ant.
            { data: 6, className: 'text-center fw-bold' }, // Est. Atual
            { data: 7 }, // Origem
            { data: 8 }  // Observação
        ],
        order: [[0, 'desc']],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Atualiza o contador de registros
    tabela.on('xhr.dt', function(e, settings, json) {
        if (json && json.recordsTotal !== undefined) {
            $('#totalRegistros').text(json.recordsTotal + ' registros');
        }
    });
}

// Inicializa os selects com Select2
function inicializarSelect2() {
    // Select de produto nos filtros
    $('#produto').select2({
        ajax: {
            url: '/estoque/listproductsselect',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results || []
                };
            }
        },
        placeholder: 'Todos os produtos',
        allowClear: true,
        minimumInputLength: 0
    });

    // Select de produto na modal de entrada
    $('#entrada_produto').select2({
        ajax: {
            url: '/estoque/listproductsselect',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results || []
                };
            }
        },
        placeholder: 'Selecione um produto',
        minimumInputLength: 0,
        dropdownParent: $('#modalEntrada')
    });

    // Select de produto na modal de saída
    $('#saida_produto').select2({
        ajax: {
            url: '/estoque/listproductsselect',
            type: 'POST',
            dataType: 'json',
            delay: 250,
            processResults: function(data) {
                return {
                    results: data.results || []
                };
            }
        },
        placeholder: 'Selecione um produto',
        minimumInputLength: 0,
        dropdownParent: $('#modalSaida')
    });
}

// Inicializa os event listeners
function inicializarEventListeners() {
    // Botão filtrar
    $('#btnFiltrar').on('click', function() {
        tabela.ajax.reload();
    });

    // Botão limpar filtros
    $('#btnLimpar').on('click', function() {
        $('#produto').val('').trigger('change');
        $('#tipo_movimento').val('');
        $('#data_inicial').val('');
        $('#data_final').val('');
        tabela.ajax.reload();
    });

    // Enter nos campos de filtro
    $('#data_inicial, #data_final').on('keypress', function(e) {
        if (e.which === 13) {
            tabela.ajax.reload();
        }
    });

    // Formulário de entrada
    $('#formEntrada').on('submit', async function(e) {
        e.preventDefault();
        await registrarEntrada();
    });

    // Formulário de saída
    $('#formSaida').on('submit', async function(e) {
        e.preventDefault();
        await registrarSaida();
    });

    // Quando selecionar um produto na saída, buscar estoque atual
    $('#saida_produto').on('change', async function() {
        const idProduto = $(this).val();
        if (idProduto) {
            await buscarEstoqueAtual(idProduto);
        } else {
            $('#saida_estoque').val('');
        }
    });
}

// Busca o estoque atual do produto
async function buscarEstoqueAtual(idProduto) {
    try {
        const formData = new FormData();
        formData.append('id_produto', idProduto);

        const response = await fetch('/estoque/getstock', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status) {
            $('#saida_estoque').val(data.estoque);
        } else {
            $('#saida_estoque').val('Erro');
            Swal.fire({
                title: 'Erro!',
                icon: 'error',
                html: data.msg,
                timer: 3000
            });
        }
    } catch (error) {
        $('#saida_estoque').val('Erro');
        console.error('Erro ao buscar estoque:', error);
    }
}

// Registra entrada de estoque
async function registrarEntrada() {
    const idProduto = $('#entrada_produto').val();
    const quantidade = $('#entrada_quantidade').val();
    const observacao = $('#entrada_observacao').val();

    if (!idProduto) {
        Swal.fire({
            title: 'Erro!',
            icon: 'error',
            html: 'Selecione um produto!',
            timer: 3000
        });
        return;
    }

    if (!quantidade || parseInt(quantidade) <= 0) {
        Swal.fire({
            title: 'Erro!',
            icon: 'error',
            html: 'Informe uma quantidade válida!',
            timer: 3000
        });
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id_produto', idProduto);
        formData.append('quantidade', quantidade);
        formData.append('observacao', observacao);

        const response = await fetch('/estoque/entrada', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status) {
            Swal.fire({
                title: 'Sucesso!',
                icon: 'success',
                html: data.msg,
                timer: 2000
            }).then(() => {
                // Limpar formulário
                $('#formEntrada')[0].reset();
                $('#entrada_produto').val('').trigger('change');
                
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEntrada'));
                modal.hide();
                
                // Recarregar tabela
                tabela.ajax.reload();
            });
        } else {
            Swal.fire({
                title: 'Erro!',
                icon: 'error',
                html: data.msg,
                timer: 3000
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            icon: 'error',
            html: 'Ocorreu um erro ao registrar a entrada: ' + error.message,
            timer: 3000
        });
    }
}

// Registra saída de estoque
async function registrarSaida() {
    const idProduto = $('#saida_produto').val();
    const quantidade = $('#saida_quantidade').val();
    const observacao = $('#saida_observacao').val();

    if (!idProduto) {
        Swal.fire({
            title: 'Erro!',
            icon: 'error',
            html: 'Selecione um produto!',
            timer: 3000
        });
        return;
    }

    if (!quantidade || parseInt(quantidade) <= 0) {
        Swal.fire({
            title: 'Erro!',
            icon: 'error',
            html: 'Informe uma quantidade válida!',
            timer: 3000
        });
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id_produto', idProduto);
        formData.append('quantidade', quantidade);
        formData.append('observacao', observacao);

        const response = await fetch('/estoque/saida', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.status) {
            Swal.fire({
                title: 'Sucesso!',
                icon: 'success',
                html: data.msg,
                timer: 2000
            }).then(() => {
                // Limpar formulário
                $('#formSaida')[0].reset();
                $('#saida_produto').val('').trigger('change');
                $('#saida_estoque').val('');
                
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalSaida'));
                modal.hide();
                
                // Recarregar tabela
                tabela.ajax.reload();
            });
        } else {
            Swal.fire({
                title: 'Erro!',
                icon: 'error',
                html: data.msg,
                timer: 3000
            });
        }
    } catch (error) {
        Swal.fire({
            title: 'Erro!',
            icon: 'error',
            html: 'Ocorreu um erro ao registrar a saída: ' + error.message,
            timer: 3000
        });
    }
}

// Exporta funções para o escopo global
window.registrarEntrada = registrarEntrada;
window.registrarSaida = registrarSaida;
window.buscarEstoqueAtual = buscarEstoqueAtual;

