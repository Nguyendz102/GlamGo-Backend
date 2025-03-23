$(document).ready(function () {
    const inputSearch = $("input.input-search");
    const formSearch = $(".form-search");
    const logo = $(".main-logo img");
    const overlaySearch = $("#search-overlay");
    const searchProduct = $(".search-product");
    const categoryList = $("#category-list");
    const productList = $("#product-list");
    const viewAllResult = $("#view-all-result");
    let inputSearchValue = "";

    inputSearch.on("keyup", async function () {
        inputSearchValue = $(this).val().trim();
        overlaySearch.addClass("active");
        searchProduct.addClass("active");
        if (inputSearchValue === "") {
            categoryList.html("");
            productList.html("");
            viewAllResult.html("");
            return;
        }
        await axios
            .post("/graphql", {
                query: `query {productSearch(name: "${inputSearchValue}") {categories { id name slug }products { id name price image price_old slug}}}`,
            })
            .then((response) => {
                const responseCate =
                    response.data.data.productSearch.categories;
                const responsePro = response.data.data.productSearch.products;

                let categoryHTML = responseCate
                    .map(
                        (category) =>
                            `<li data-category-id="${category.id}"><a href="/${category.slug}">${category.name}</a></li>`
                    )
                    .join("");

                let productHTML = responsePro
                    .map(
                        (product) =>
                            `
                            <div class="product-item">
                                <div class="product-image">
                                    <a href="/${product.slug}">
                                        <img src="/storage/${product.image}"
                                            alt="">
                                    </a>
                                </div>

                                <div class="product-info">
                                    <a href="/${product.slug}">${
                                product.name
                            }</a>
                                    <p><span class="price old">${
                                        product.price_old == 0
                                            ? ""
                                            : product.price_old
                                    }</span> <span class="price new">${
                                product.price
                            }</span>
                                    </p>
                                </div>
                            </div>
                        `
                    )
                    .join("");

                categoryList.html(categoryHTML);
                productList.html(productHTML);
                viewAllResult.html(`
                    <button type="button">VIEW ALL RESULTS</button>
                    `);
            })
            .catch((error) => {
                console.error("error:", error);
                categoryList.html("");
                productList.html("");
                viewAllResult.html("<p>This product was not found.</p>");
            });

        if ($(this).val() === "") {
            overlaySearch.removeClass("active");
            searchProduct.removeClass("active");
        }
    });
    //hover
    categoryList.on("mouseover", "li", async function () {
        let categoryId = $(this).data("category-id");

        try {
            const response = await axios.post("/graphql", {
                query: `query {
                    productSearch(name: "${inputSearchValue}", category_id: ${categoryId}) {products {  id name price image price_old slug }
                    }
                }`,
            });


            const responsePro = response.data.data.productSearch.products;

            if (!responsePro || responsePro.length === 0) {
                productList.html("<p>This product was not found.</p>");
                viewAllResult.html("");
                return;
            }

            let productHTML = responsePro
                .map(
                    (product) =>
                        `
                    <div class="product-item">
                        <div class="product-image">
                            <a href="/${product.slug}">
                                <img src="/storage/${product.image}"
                                    alt="">
                            </a>
                        </div>

                        <div class="product-info">
                            <a href="/${product.slug}">${product.name}</a>
                            <p><span class="price old">${
                                product.price_old == 0 ? "" : product.price_old
                            }</span> <span class="price new">${
                            product.price
                        }</span>
                            </p>
                        </div>
                    </div>
                `
                )
                .join("");

            productList.html(productHTML);
            viewAllResult.html(`
                <button type="button">VIEW ALL RESULTS</button>
                `);
        } catch (error) {
            console.error("error:", error);
        }
    });

    $("body").on("click", function (e) {
        if (!formSearch.is(e.target) && formSearch.has(e.target).length === 0) {
            overlaySearch.removeClass("active");
            searchProduct.removeClass("active");
        }
    });
});
