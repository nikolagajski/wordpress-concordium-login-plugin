import {detectConcordiumProvider} from '@concordium/browser-wallet-api-helpers';
import {AccountTransactionSignature} from "@concordium/web-sdk";
import axios, {AxiosError, AxiosResponse} from "axios";

// @ts-ignore
const {__} = wp.i18n;

interface WPJson<T> {
    success: boolean
    message: string | null
    messages: Array<any> | null
    data: T
}

interface AuthJson {
    redirect: string
}

interface NonceJson {
    nonce: string
}

interface ConcordiumButton extends HTMLButtonElement {
    changeContent(value: string, divSelector?: string): void
}

class LoginButtons {
    private buttons: HTMLCollectionOf<ConcordiumButton>;

    constructor(className: string) {
        // @ts-ignore
        this.buttons = document.getElementsByClassName(className)
        this.apply(function (n: ConcordiumButton) {
            n.innerHTML = n.innerHTML.replace(
                // @ts-ignore
                __('Concordium', 'concordium-login'),
                '<span class="concordiumButtonMessage">'
                // @ts-ignore
                + __('Concordium', 'concordium-login')
                + '</span>');

            // @ts-ignore
            n.changeContent = function (value: string, selector: string = '.concordiumButtonMessage'): void {
                const selected = this.querySelector(selector)
                if (selected) {
                    selected.innerHTML = value
                }
            }
        })
    }

    public apply(callback: (n: ConcordiumButton) => void) {
        for (var i = 0; i < this.buttons.length; i++) {
            callback(this.buttons[i])
        }
    }
}

export async function run() {
    const buttons = new LoginButtons('concordium_submit')
    buttons.apply(btn => btn.disabled = true)

    // @ts-ignore
    const ajaxurl: string = CONCORDIUM_VAL.ajaxurl;

    buttons.apply(btn => {
        btn.disabled = true
        btn.changeContent(
            // @ts-ignore
            __('Concordium is not installed', 'concordium-login')
        )
    })

    detectConcordiumProvider()
        .then((provider) => {
            // The API is ready for use.
            async function connect(btn: ConcordiumButton): Promise<any> {
                provider
                    .connect()
                    .then(async (accountAddress): Promise<void> => {
                        btn.changeContent(
                            // @ts-ignore
                            __('Signing Nonce', 'concordium-login')
                        )
                        // The wallet is connected to the dApp.
                        if (accountAddress === undefined) {
                            throw new Error
                        }

                        const returnInput = btn.form?.querySelector('input[name="redirect_to"]')
                        const rememberInput = btn.form?.querySelector('input[name="rememberme"]')
                        let returnVal = ''
                        let remember = false

                        if (returnInput instanceof HTMLInputElement) {
                            returnVal = returnInput.value
                        }

                        if (rememberInput instanceof HTMLInputElement) {
                            remember = rememberInput.checked
                        }

                        const res: AxiosResponse<WPJson<NonceJson>> = await axios<WPJson<NonceJson>>({
                            method: 'post',
                            url: ajaxurl,
                            data: {
                                accountAddress: accountAddress,
                                action: 'concordium_nonce',
                            },
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        });

                        if (res.status != 200) {
                            throw new Error
                        }

                        const text = res.data.data.nonce
                        const signed: AccountTransactionSignature = await provider.signMessage(accountAddress, text)

                        const res2: AxiosResponse<WPJson<AuthJson>> = await axios<WPJson<AuthJson>>({
                            method: 'post',
                            url: ajaxurl,
                            data: {
                                accountAddress: accountAddress,
                                signed: signed,
                                text: text,
                                return: returnVal,
                                remember: remember,
                                action: 'concordium_auth',
                            },
                            headers: {
                                'Content-Type': 'multipart/form-data',
                            }
                        });

                        if (res2.status != 200) {
                            throw new Error
                        }

                        btn.disabled = false
                        btn.changeContent(
                            // @ts-ignore
                            __('Nonce is signed. Submitting form.', 'concordium-login')
                        )

                        btn.form?.submit()
                    })
                    .catch((e: AxiosError | Error) => {
                        console.log(e)
                        btn.classList.replace("button-default", "button-primary")
                        if (e instanceof AxiosError
                            && e.response && e.response.data && e.response.data.message) {
                            btn.changeContent(e.response.data.message)
                        } else {
                            btn.changeContent(
                                // @ts-ignore
                                __('Connection to the Concordium browser wallet was rejected. Try Again.', 'concordium-login')
                            )
                        }

                        btn.disabled = false
                    });
            }

            buttons.apply(btn => {
                btn.disabled = false
                btn.changeContent(
                    // @ts-ignore
                    __('Concordium', 'concordium-login')
                )
                btn.addEventListener(
                    'click',
                    (event) => {
                        btn.classList.replace("button-primary", "button-default")
                        event.preventDefault()
                        btn.disabled = true
                        btn.changeContent(
                            // @ts-ignore
                            __('Connecting...', 'concordium-login')
                        )
                        connect(btn)
                    },
                    false
                )
            })
        })
        .catch((e) => {
            console.log('Connection to the Concordium browser wallet timed out.')
        });
}

run()
