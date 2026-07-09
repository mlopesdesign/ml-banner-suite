# ML Banner Suite

Plugin comercial WordPress para gestão profissional de banners com integração isolada do Google AdSense, atualização automática via GitHub Releases e arquitetura modular pronta para evolução comercial.

## Versão atual

**3.0.0** — Módulo Google AdSense

## Instalação

1. Baixe o ZIP da última release em [github.com/mlopesdesign/ml-banner-suite/releases](https://github.com/mlopesdesign/ml-banner-suite/releases).
2. No WordPress: **Plugins > Adicionar novo > Enviar plugin**.
3. Selecione o ZIP e clique **Instalar agora**.
4. **Ative** o plugin.
5. Abra **ML Banner Suite** na barra lateral do admin.

A atualização por cima da versão instalada preserva todas as configurações, banners, licenças e tabelas do banco.

## Atualizações automáticas

O plugin consulta o GitHub Releases periodicamente. O asset oficial (ZIP) é baixado direto da release marcada como `Latest`. Nenhum token ou credencial é exposto.

- Detecção de nova versão no painel WP Admin > Plugins
- Cache local para evitar chamadas desnecessárias
- Fallback para `readme.txt` local quando o GitHub está fora do ar
- Botão "Verificar novamente" invalida o cache

## Estrutura

```
ml-banner-suite/
├── ml-banner-suite.php        # bootstrap principal
├── readme.txt                  # metadados WP
├── uninstall.php               # limpeza opcional
├── includes/                   # classes core (loader, post types, updater, etc)
├── admin/                      # telas e menus administrativos
├── public/                     # assets e templates frontend
├── modules/                    # módulos isolados (ex.: adsense/)
├── assets/                     # CSS, JS, ícones compartilhados
├── languages/                  # i18n (pt_BR, en_US, es_ES)
└── lib/plugin-update-checker/  # biblioteca oficial PUC v5p6 (vendored)
```

## Padrão de qualidade

Este plugin segue o padrão premium de plugins do `mlopesdesign`:

- Identidade técnica imutável entre versões
- Slug `ml-banner-suite/` preservado em todas as atualizações
- Atualização via GitHub Releases com asset ZIP oficial
- Capability checks, nonces, sanitização e escaping em todos os pontos
- i18n completo (textdomain `ml-banner-suite`)
- Sem debug, `var_dump`, credenciais ou logs desnecessários no pacote

## Licença

GPL-2.0-or-later

## Autor

Marcio Lopes — [mlopesdesign.com.br](https://mlopesdesign.com.br)